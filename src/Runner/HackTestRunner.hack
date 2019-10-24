/*
*  Copyright (c) 2018-present, Facebook, Inc.
*  All rights reserved.
*
*  This source code is licensed under the MIT license found in the
*  LICENSE file in the root directory of this source tree.
*
*/

namespace Facebook\HackTest;

use namespace HH\Lib\{Keyset, Str};

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
    (function(
      classname<HackTest>,
      ?string,
      ?arraykey,
      TestProgressEvent,
    ): Awaitable<void>) $progress_writer,
    (function(TestResult): Awaitable<void>) $result_writer,
  ): Awaitable<dict<string, dict<string, ?\Throwable>>> {
    $errors = dict[];
    $files = keyset[];
    foreach ($paths as $path) {
      $files = Keyset\union($files, (new FileRetriever($path))->getTestFiles());
    }

    $classes_or_exceptions = ClassRetriever::forFiles($files);
    $classes = vec[];
    foreach ($classes_or_exceptions as $path => $coe) {
      if ($coe is _Private\WrappedResult<_>) {
        $classes[] = $coe->getResult()->getTestClassName();
        continue;
      }
      $wex = $coe as _Private\WrappedException<_>;
      // FIXME: errors should be keyable by file, not just classname
      /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
      await \HH\Lib\Experimental\IO\request_error()->writeAsync(
        Str\format("Failed to process file %s: %s", $path, $wex->getException()->getMessage())
      );
    }

    $class_filter = $filters['classes'];
    $method_filter = $filters['methods'];
    foreach ($classes as $classname) {
      if ($classname === null) {
        continue;
      }
      if (!$class_filter($classname)) {
        continue;
      }
      /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
      await $progress_writer(
        $classname,
        null,
        null,
        TestProgressEvent::STARTING,
      );
      $test_case = new $classname();
      /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
      $errors[$classname] = await $test_case->runTestsAsync(
        $method ==> $method_filter($classname, $method),
        $progress_writer,
        $result_writer,
      );
      /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
      await $progress_writer(
        $classname,
        null,
        null,
        TestProgressEvent::FINISHED,
      );
    }
    return $errors;
  }
}
