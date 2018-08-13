<?hh // strict
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

  public static async function runAsync(
    vec<string> $paths,
    (function(TestResult): void) $writer,
  ): Awaitable<dict<string, dict<string, ?\Throwable>>> {
    $errors = dict[];
    $files = keyset[];
    foreach ($paths as $path) {
      $files = Keyset\union($files, (new FileRetriever($path))->getTestFiles());
    }

    $classes = ClassRetriever::forFiles($files)
      |> Vec\map($$, $r ==> $r->getTestClassName());

    foreach ($classes as $classname) {
      $test_case = new $classname();
      /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
      $errors[$classname] = await $test_case->runAsync($writer);
    }
    return $errors;
  }
}
