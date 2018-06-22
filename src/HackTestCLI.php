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
    $this->getStdout()
      ->write("HackTest 1.0 by Wilson Lin and contributors.\n\n");

    $output = await HackTestRunner::runAsync(
      $this->getArguments(),
      $this->verbose,
      inst_meth($this, 'writeProgress'),
    );
    $output_chunks = Str\chunk($output, 64);
    foreach ($output_chunks as $chunk) {
      $this->getStdout()->write($chunk);
    }

    return HackTestRunner::getExit();
  }

  public function writeProgress(TestResult $progress): void {
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
    $this->getStdout()->write($status);
  }
}
