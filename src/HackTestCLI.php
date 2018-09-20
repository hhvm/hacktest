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

use type Facebook\CLILib\CLIWithRequiredArguments;
use namespace Facebook\CLILib\CLIOptions;
use namespace HH\Lib\Str;

/** The main `hacktest` CLI */
final class HackTestCLI extends CLIWithRequiredArguments {

  private bool $verbose = false;
  private ?string $pattern = null;

  <<__Override>>
  public static function getHelpTextForRequiredArguments(): vec<string> {
    return vec['SOURCE_PATH'];
  }

  <<__Override>>
  protected function getSupportedOptions(): vec<CLIOptions\CLIOption> {
    return vec[
      CLIOptions\flag(
        () ==> {
          $this->verbose = true;
        },
        "Increase output verbosity",
        '--verbose',
        '-v',
      ),
      CLIOptions\with_required_string(
        ($value) ==> {
          $this->pattern = $value;
        },
        'Only run tests with method names matching this shell pattern', 
        '--name',
        '-n', 
      ),
    ];
  }

  <<__Override>>
  public async function mainAsync(): Awaitable<int> {
    $errors = await HackTestRunner::runAsync(
      $this->getArguments(),
      $this->pattern,
      async $result ==> await $this->writeProgressAsync($result),
    );
    $num_tests = 0;
    $num_msg = 0;
    $num_failed = 0;
    $num_skipped = 0;
    $output = '';
    foreach ($errors as $class => $result) {
      $file = (new \ReflectionClass($class))->getFileName() as string;
      foreach ($result as $test_params => $err) {
        $num_tests++;
        if ($err === null) {
          continue;
        }
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
        if ($this->verbose) {
          $output .= Str\format(
            "%s\n\n%s",
            $err->getMessage(),
            $err->getTraceAsString(),
          );
        } else {
          $output .= $err->getMessage();
          $trace = Str\split($err->getTraceAsString(), '#');
          $out = '';
          foreach ($trace as $line) {
            if (Str\contains($line, $file)) {
              $out .= Str\slice($line, 2);
            }
          }
          if (!Str\is_empty($out)) {
            $output .= "\n\n".$out;
          }
        }
      }
    }
    $num_errors = $num_msg - $num_failed - $num_skipped;
    $exit = ExitCode::SUCCESS;
    if ($num_errors > 0) {
      $exit = ExitCode::ERROR;
    } else if ($num_failed > 0) {
      $exit = ExitCode::FAILURE;
    }
    $output .= Str\format(
      "\n\nSummary: %d test(s), %d passed, %d failed, %d skipped, %d error(s).\n",
      $num_tests,
      ($num_tests - $num_msg),
      $num_failed,
      $num_skipped,
      $num_errors,
    );
    await $this->getStdout()->writeAsync($output);

    return $exit;
  }

  public async function writeProgressAsync(
    TestResult $progress,
  ): Awaitable<void> {
    $status = '';
    switch ($progress) {
      case TestResult::PASSED:
        $status = '.';
        break;
      case TestResult::SKIPPED:
        $status = 'S';
        break;
      case TestResult::FAILED:
        $status = 'F';
        break;
      case TestResult::ERROR:
        $status = 'E';
        break;
    }
    await $this->getStdout()->writeAsync($status);
  }
}
