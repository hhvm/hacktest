/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use function Facebook\FBExpect\expect;
use namespace Facebook\CLILib\TestLib;
use type Facebook\CLILib\Terminal;
// @oss-disable: <<Oncalls('hack')>>
final class ExitCodeTest extends HackTest {

  public async function testExitSuccess(): Awaitable<void> {
    $cli = self::makeCLI(vec['', 'tests/clean/hsl/vec']);
    $res = await $cli->mainAsync();
    expect($res)->toBeSame(ExitCode::SUCCESS);
  }

  public async function testExitFailure(): Awaitable<void> {
    $cli = self::makeCLI(vec['', 'tests/dirty/DirtyAsyncTest.php']);
    $res = await $cli->mainAsync();
    expect($res)->toBeSame(ExitCode::FAILURE);
  }

  public async function testExitError(): Awaitable<void> {
    $cli = self::makeCLI(vec['', 'tests/dirty']);
    $res = await $cli->mainAsync();
    expect($res)->toBeSame(ExitCode::ERROR);
  }

  private static function makeCLI(vec<string> $argv): HackTestCLI {
    $stdin = new TestLib\StringInput();
    $stdout = new TestLib\StringOutput();
    $stderr = new TestLib\StringOutput();
    $terminal = new Terminal($stdin, $stdout, $stderr);
    return new HackTestCLI($argv, $terminal);
  }
}
