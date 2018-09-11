<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use type Facebook\HackTest\HackTestCase;

<<Oncalls('hack')>>
final class DirtyErrorTest extends HackTestCase {

  public function testInvariantException(): void {
    invariant(0 === 1, 'This should count as an error rather than a test failure');
  }

  public function testArgumentCountError(int $_bad_arg): void {}
}
