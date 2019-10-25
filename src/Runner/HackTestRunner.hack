/*
*  Copyright (c) 2018-present, Facebook, Inc.
*  All rights reserved.
*
*  This source code is licensed under the MIT license found in the
*  LICENSE file in the root directory of this source tree.
*
*/

namespace Facebook\HackTest;

use namespace HH\Lib\Keyset;

/* HHAST_IGNORE_ALL[DontAwaitInALoop] */

abstract final class HackTestRunner {
  const type TMethodFilter = (function(
    classname<HackTest>,
    \ReflectionMethod,
  ): bool);
  const type TFilters = shape(
    'classes' => (function(classname<HackTest>): bool),
    'methods' => this::TMethodFilter,
  );

  public static async function runAsync(
    vec<string> $paths,
    this::TFilters $filters,
    (function(ProgressEvent): Awaitable<void>) $progress_callback,
  ): Awaitable<void> {
    await $progress_callback(new TestRunStartedProgressEvent());
    await using (
      (new TestRunFinishedProgressEvent())->onScopeExit($progress_callback)
    );

    $files = keyset[];
    foreach ($paths as $path) {
      $files = Keyset\union($files, (new FileRetriever($path))->getTestFiles());
    }

    $classes_or_exceptions = ClassRetriever::forFiles($files);
    $classes = vec[];
    foreach ($classes_or_exceptions as $path => $coe) {
      if ($coe is _Private\WrappedResult<_>) {
        try {
          $classes[] = tuple($path, $coe->getResult()->getTestClassName());
        } catch (InvalidTestClassException $ex) {
          await $progress_callback(new FileErrorProgressEvent($path, $ex));
        }
        continue;
      }
      $wex = $coe as _Private\WrappedException<_>;
      await $progress_callback(new FileErrorProgressEvent(
        $path,
        $wex->getException() as InvalidTestFileException,
      ));
    }

    $class_filter = $filters['classes'];
    $method_filter = $filters['methods'];
    foreach ($classes as list($path, $classname)) {
      if ($classname === null) {
        continue;
      }
      if (!$class_filter($classname)) {
        continue;
      }
      await $progress_callback(new StartingTestClassEvent($path, $classname));
      await using (
        (new FinishedTestClassEvent($path, $classname))->onScopeExit(
          $progress_callback,
        )
      ) {
        $test_case = new $classname();
        await $test_case->runTestsAsync(
          $method ==> $method_filter($classname, $method),
          $progress_callback,
        );
      }
    }
  }
}
