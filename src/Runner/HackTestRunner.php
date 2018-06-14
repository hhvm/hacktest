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
use HH\Lib\{Vec, C};

class HackTestRunner {

  public static function run(vec<string> $paths): void {
    $test_errors = [];
    $num_errors = 0;
    $num_tests = 0;

    foreach ($paths as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $class_name = new ClassRetriever($file)->getTestClassName();
        $class = $file->getClass($class_name);
        $methods = new MethodRetriever($class)->getTestMethods();
        $htc = new HackTestCase($class_name, $methods);
        $test_errors[] = $htc->run();
        $num_errors += C\count(C\lastx($test_errors));
        $num_tests++;
      }
    }

    \printf("\n\nThere were %d error(s):\n\n", $num_errors);
    $num_error = 0;
    foreach ($test_errors as $test_error) {
      foreach ($test_error as $error) {
        \printf("%d) %s ", ++$num_error, $error->__toString());
      }
    }

    \printf("\n\nSummary: %d test(s), %d passed, %d failed.\n",
      $num_tests, $num_tests - $num_errors, $num_errors);
  }
}
