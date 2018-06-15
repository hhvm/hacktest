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
use HH\Lib\{Str, Dict, C};

/** The main `hacktest` CLI */
final class HackTestCLI extends CLIWithRequiredArguments {

  private int $verbosity = 0;

  <<__Override>>
  public static function getHelpTextForRequiredArguments(): vec<string> {
    return vec['SOURCE_PATH'];
  }

  <<__Override>>
  protected function getSupportedOptions(): vec<CLIOptions\CLIOption> {
    return vec[
      CLIOptions\flag(
        () ==> { $this->verbosity++; },
        "Increase output verbosity",
        '--verbose',
        '-v',
      ),
    ];
  }

  <<__Override>>
  public async function mainAsync(): Awaitable<int> {
    $this->getStdout()->write("HackTest 1.0 by Wilson Lin and contributors.\n\n");
    $test_results = await HackTestRunner::runAsync($this->getArguments());
    $num_tests = C\count($test_results);
    $test_errors = Dict\filter($test_results, $res ==> $res instanceof \Exception);
    $num_errors = C\count($test_errors);
    $num_passed = $num_tests - $num_errors;
    $this->writeVerbose($test_results, $num_errors);
    $this->getStdout()->write
      ("Summary: ".$num_tests." test(s), ".$num_passed." passed, ".$num_errors." failed.\n");
    return 0;
  }

  public function writeVerbose(dict<string, mixed> $test_errors, int $num_errors): void {
    if (!$this->verbosity) {
      return;
    }
    $this->getStdout()->write("There were ".$num_errors." error(s):\n\n");
    $num_error = 0;
    foreach ($test_errors as $test_info => $exception) {
      $exception = $test_errors[$test_info];
      if ($exception instanceof \Exception) {
        $err = Str\split($test_info, '.');
        $this->getStdout()->write("Class: ".$err[0]."\nMethod: ".$err[1]."\n");
        $this->getStdout()->write(++$num_error.") ".$exception->__toString()."\n\n");
      }
    }
  }

}
