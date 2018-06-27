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

use type Facebook\DefinitionFinder\FileParser;
use namespace HH\Lib\{C, Str};

abstract final class HackTestRunner {

  public static async function runAsync(
    vec<string> $paths,
    (function(TestResult): void) $writer,
  ): Awaitable<dict<string, dict<string, ?\Throwable>>> {
    $errors = dict[];
    foreach ($paths as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $classname = (new ClassRetriever($file))->getTestClassName();
        $test_case = new $classname();
        /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
        $errors[$classname] = await $test_case->runAsync($writer);
      }
    }
    return $errors;
  }
}
