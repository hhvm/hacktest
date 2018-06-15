<?hh  // strict
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use Facebook\HackTest\{FileRetriever, ClassRetriever, MethodRetriever};
use HH\Lib\Dict;

class HackTestRunner {

  public static async function runAsync(vec<string> $paths): Awaitable<dict<string, mixed>> {
    $test_results = dict[];
    foreach ($paths as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $class_name = new ClassRetriever($file)->getTestClassName();
        $class = $file->getClass($class_name);
        $methods = new MethodRetriever($class)->getTestMethods();
        $htc = new HackTestCase($class_name, $methods);
        $result = await $htc->runAsync();
        $test_results = Dict\merge($result, $test_results);
      }
    }

    return $test_results;
  }
}
