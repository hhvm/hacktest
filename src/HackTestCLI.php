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
    ];
  }

  <<__Override>>
  public async function mainAsync(): Awaitable<int> {
    $errors = await HackTestRunner::runAsync(
      $this->getArguments(),
      async ($class, $method, $dataKey, $event) ==>
        await $this->writeProgressAsync($class, $method, $dataKey, $event),
      async $result ==> await $this->writeResultAsync($result),
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
        if ($err is SkippedTestException) {
          $num_skipped++;
          $output .= 'Skipped: '.$err->getMessage();
          continue;
        }
        if ($err is ExpectationFailedException) {
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
    classname<HackTest> $class,
    ?string $method,
    ?arraykey $data_key,
    TestProgressEvent $event,
  ): Awaitable<void> {
    if (!$this->verbose) {
      return;
    }

		if ($method is nonnull) {
      $text = '  ::'.$method;
			if ($data_key is nonnull) {
				$text .= '['.((string)$data_key).']';
			}
      $text .= '> ';
		} else {
      $text = $class.'> ';
		}

    switch ($event) {
      case TestProgressEvent::CALLING_DATAPROVIDERS:
        $text .= 'calling data providers...';
        break;
        case TestProgressEvent::STARTING:
        $text .= 'starting...';
        break;
        case TestProgressEvent::FINISHED:
        $text .= '...complete.';
        break;
    }
    await $this->getStdout()->writeAsync($text."\n");
  }

  public async function writeResultAsync(
    TestResult $progress,
  ): Awaitable<void> {
    $v = $this->verbose;
    switch ($progress) {
      case TestResult::PASSED:
        $status = $v ? "PASS\n" : '.';
        break;
      case TestResult::SKIPPED:
        $status = $v ? "SKIP\n" : 'S';
        break;
      case TestResult::FAILED:
        $status = $v ? "FAIL\n" : 'F';
        break;
      case TestResult::ERROR:
        $status = $v ? "ERROR\n" : 'E';
        break;
    }
    await $this->getStdout()->writeAsync($status);
  }
}
