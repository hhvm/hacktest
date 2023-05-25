<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use function Facebook\FBExpect\expect;
use type Facebook\HackTest\{DataProvider, HackTest};
use namespace HH\Lib\Str;

// @oss-disable: <<Oncalls('hack')>>
final class DataProviderWithPeriodInKeyTest extends HackTest {
  public function provideWithPeriod()[]: dict<string, (string)> {
    return dict['per.iod' => tuple('str')];
  }

  <<DataProvider('provideWithPeriod')>>
  public function testPeriodInKey(string $v): void {
    expect($v)->toNotEqual('str');
  }
}
