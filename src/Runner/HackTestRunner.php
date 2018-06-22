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

  private static ExitCode $exit = ExitCode::SUCCESS;

  public static async function runAsync(
    vec<string> $paths,
    bool $verbosity,
    (function(TestResult): void) $callback,
  ): Awaitable<string> {
    $errors = dict[];
    $output = '';
    $verbose = '';
    $num_tests = 0;
    $num_msg = 0;
    $num_failed = 0;
    $num_skipped = 0;

    foreach ($paths as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $classname = (new ClassRetriever($file))->getTestClassName();
        $test_case = new $classname();
        /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
        $errors[$classname] = await $test_case->runAsync($callback);
        $num_tests += $test_case->getNumTests();
      }
    }

    foreach ($errors as $class => $result) {
      foreach ($result as $test_params => $err) {
        $num_msg++;
        if (Str\contains($test_params, '.')) {
          list($method, $num, $data) = Str\split($test_params, '.');
          $verbose .= "\n\n".$num_msg.") ".$class."::".$method.
          " with data set #".$num." ".$data."\n";
        } else {
          $verbose .= "\n\n".$num_msg.") ".$class."::".$test_params."\n";
        }
        if ($err instanceof \Exception) {
          if ($err instanceof SkippedTestException) {
            $num_skipped++;
            $verbose .= 'Skipped: '.$err->getMessage();
            continue;
          } else if ($err instanceof \PHPUnit_Framework_ExpectationFailedException) {
            $num_failed++;
          }
        }
        $verbose .= $err->__toString();
      }
    }
    if ($verbosity) {
      $output .= $verbose;
    }
    $num_errors = $num_msg - $num_failed - $num_skipped;
    if ($num_errors > 0) {
      self::$exit = ExitCode::ERROR;
    } else if ($num_failed > 0) {
      self::$exit = ExitCode::FAILURE;
    }

    $output .= "\n\nSummary: ".
      $num_tests.
      " test(s), ".
      ($num_tests - $num_msg).
      " passed, ".
      ($num_failed).
      " failed, ".
      $num_skipped.
      " skipped, ".
      $num_errors.
      " error(s).\n";

    return $output;
  }

  public static function getExit(): ExitCode {
    return self::$exit;
  }

}
