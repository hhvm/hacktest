/*
*  Copyright (c) 2018-present, Facebook, Inc.
*  All rights reserved.
*
*  This source code is licensed under the MIT license found in the
*  LICENSE file in the root directory of this source tree.
*
*/

namespace Facebook\HackTest;

use namespace HH\Lib\{Keyset, Vec};

abstract final class HackTestRunner {
  const type TFilters = shape(
    'classes' => (function(classname<HackTest>): bool),
    'methods' => (function(classname<HackTest>, string): bool),
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

    $classes = ClassRetriever::forFiles($files)
      |> Vec\map($$, $r ==> $r->getTestClassName());

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
