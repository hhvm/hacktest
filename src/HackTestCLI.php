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

/** The main `hacktest` CLI */
final class HackTestCLI extends CLIWithRequiredArguments {
  <<__Override>>
  public static function getHelpTextForRequiredArguments(): vec<string> {
    return vec['SOURCE_PATH'];
  }


  <<__Override>>
  protected function getSupportedOptions(): vec<CLIOptions\CLIOption> {
    return vec[];
  }

  <<__Override>>
  public async function mainAsync(): Awaitable<int> {
    $this->getStdout()->write("HackTest 1.0 by Wilson Lin and contributors.\n\n");
    HackTestRunner::run($this->getArguments());
    return 0;
  }
}
