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
use namespace HH\Lib\C;

abstract final class HackTestRunner {

  public static async function runAsync(vec<string> $paths, bool $verbosity): Awaitable<string> {
    $results = dict[];
    $output = '';
    $num_tests = 0;
    $num_errors = 0;

    foreach ($paths as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $classname = new ClassRetriever($file)->getTestClassName();
        $class = $file->getClass($classname);
        $methods = new MethodRetriever($class)->getTestMethods();
        $htc = new HackTestCase($classname, $methods);
        $results[$classname] = $htc->runAsync();
      }
    }

    $num_error = 0;
    $test_results = dict[];
    foreach ($results as $class => $res) {
      $test_results[$class] = await $res;
    }
    $output .= HackTestCase::getOutput();
    foreach ($test_results as $class => $result) {
      $num_tests += C\count($result);
      foreach ($result as $method => $res) {
        if ($res instanceof \Exception) {
          $num_errors++;
          if ($verbosity) {
            $output .= "\n\nClass: $class\nMethod: $method\n";
            $output .= ++$num_error.") ".$res->__toString();
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
