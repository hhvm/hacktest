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
use namespace HH\Lib\Str;

abstract final class HackTestRunner {

  public static async function runAsync(
    vec<string> $paths,
    bool $verbosity,
    (function(string): void) $callback,
  ): Awaitable<string> {
    $errors = dict[];
    $output = '';
    $verbose = '';
    $num_tests = 0;
    $num_error = 0;
    $num_skipped = 0;

    foreach ($paths as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $classname = new ClassRetriever($file)->getTestClassName();
        $class = $file->getClass($classname);
        $methods = new MethodRetriever($class)->getTestMethods();
        $test_case = new HackTestCase($classname, $methods);
        $errors[$classname] = await $test_case->runAsync($callback);
        $num_tests += $test_case->getNumTests();
      }
    }

    foreach ($errors as $class => $result) {
      foreach ($result as $test_params => $exception) {
        $num_error++;
        if (Str\contains($test_params, '.')) {
          list($method, $num, $data) = Str\split($test_params, '.');
          $verbose .= "\n\n$num_error) $class::$method".
          " with data set #$num $data\n";
        } else {
          $verbose .= "\n\n$num_error) $class::$test_params\n";
        }
        if ($exception instanceof SkippedTestException) {
          $num_skipped++;
          $verbose .= 'Skipped: '.$exception->getMessage();
        } else {
          $verbose .= $exception->__toString();
        }
      }
    }

    if ($verbosity) {
      $output .= $verbose;
    }

    $output .= "\n\nSummary: ".
      $num_tests.
      " test(s), ".
      ($num_tests - $num_error).
      " passed, ".
      ($num_error - $num_skipped).
      " failed, ".
      $num_skipped.
      " skipped.\n";

    return $output;
  }
}
