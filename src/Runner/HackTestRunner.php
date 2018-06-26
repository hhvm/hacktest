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

  private static ExitCode $exit = ExitCode::SUCCESS;

  public static async function runAsync(
    vec<string> $paths,
    bool $verbosity,
    (function(TestResult): void) $callback,
  ): Awaitable<string> {
    $errors = dict[];
    $output = '';
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
          list($method, $tuple_num, $data) = Str\split($test_params, '.');
          $output .= Str\format(
            "\n\n%d) %s::%s with data set #%s %s\n",
            $num_msg,
            $class,
            $method,
            $tuple_num,
            $data,
          );
        } else {
          $output .=
            Str\format("\n\n%d) %s::%s\n", $num_msg, $class, $test_params);
        }
        if ($err instanceof SkippedTestException) {
          $num_skipped++;
          $output .= 'Skipped: '.$err->getMessage();
          continue;
        } else if (
          \is_a($err, 'PHPUnit\\Framework\\ExpectationFailedException', true) ||
          \is_a($err, 'PHPUnit_Framework_ExpectationFailedException', true)
        ) {
          $num_failed++;
        }
        $trace = Str\split($err->getTraceAsString(), '#');
        $out = '';
        foreach ($trace as $line) {
          if (Str\contains($line, $class)) {
            $out .= Str\slice($line, 2);
          }
        }
        if ($verbosity) {
          $output .= Str\format(
            "%s\n\n%s",
            $err->getMessage(),
            $err->getTraceAsString(),
          );
        } else {
          $output .= $err->getMessage();
          if (!Str\is_empty($out)) {
            $output .= "\n\n".$out;
          }
        }
      }
    }
    $num_errors = $num_msg - $num_failed - $num_skipped;
    if ($num_errors > 0) {
      self::$exit = ExitCode::ERROR;
    } else if ($num_failed > 0) {
      self::$exit = ExitCode::FAILURE;
    }

    $output .= Str\format(
      "\n\nSummary: %d test(s), %d passed, %d failed, %d skipped, %d error(s).\n",
      $num_tests,
      ($num_tests - $num_msg),
      $num_failed,
      $num_skipped,
      $num_errors,
    );

    return $output;
  }

  public static function getExit(): ExitCode {
    return self::$exit;
  }

}
