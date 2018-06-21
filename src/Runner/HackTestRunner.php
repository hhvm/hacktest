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

  public static async function runAsync(vec<string> $paths, bool $verbosity): Awaitable<string> {
    $errors = dict[];
    $output = '';
    $num_tests = 0;
    $num_errors = 0;

    foreach ($paths as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $classname = (new ClassRetriever($file))->getTestClassName();
        $class = $file->getClass($classname);
        $methods = (new MethodRetriever($class))->getTestMethods();
        $test_case = new HackTestCase($classname, $methods);
        $errors[$classname] = await $test_case->runAsync();
        $num_tests += $test_case->getNumTests();
      }
    }

    foreach ($errors as $class => $result) {
      foreach ($result as $method => $exception) {
        $num_errors++;
        if ($verbosity) {
          if (Str\contains($method, '.')) {
            $test_info = Str\split($method, '.');
            $method = $test_info[0];
            $dataset_num = $test_info[1];
            $output .= "\n\n$num_errors) $class::$method".
              " with data set #$dataset_num\n$exception";
          } else {
            $output .= "\n\n$num_errors) $class::$method\n$exception";
          }
        }
      }
    }

    $output .=
      "\n\nSummary: ".
      $num_tests.
      " test(s), ".
      ($num_tests - $num_errors).
      " passed, ".
      $num_errors.
      " failed.\n";

    return $output;
  }
}
