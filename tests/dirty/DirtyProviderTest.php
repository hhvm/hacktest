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
final class DirtyProviderTest extends HackTest {

  public function provideDirtyData(): vec<(Traversable<arraykey>)> {
    $elements = vec['the', 'quicky', 'brown', 'fox', 1];
    return vec[
      tuple($elements),
      tuple(new Vector($elements)),
      tuple(new Set($elements)),
      tuple(new Map($elements)),
      tuple(vec($elements)),
      tuple(keyset($elements)),
      tuple(dict($elements)),
      tuple(HackLibTestTraversables::getIterator($elements)),
    ];
  }

  <<DataProvider('provideDirtyData')>>
  public function testDirtyData(Traversable<arraykey> $traversable): void {
    expect(Str\join($traversable, '-'))->toBeSame('the-quick-brown-fox-1');
  }

  public function provideNoData(): vec<(int)> {
    return vec[];
  }

  <<DataProvider('provideNoData')>>
  public function testNoData(int $a): void {
    expect($a)->toBeSame(1);
  }

  <<DataProvider('provideNoData')>>
  public function testNoDataDup(int $a): void {
    expect($a)->toBeSame(1);
  }

  public function provideError(): vec<(int, int)> {
    invariant(0 === 1, 'This test depends on a provider that throws an error.');
    return vec[
      tuple(1, 2),
      tuple(2, 1)
    ];
  }

  <<DataProvider('provideError')>>
  public function testProviderError(int $_a, int $_b): void {}

  <<DataProvider('provideError')>>
  public function testProviderErrorDup(int $_a, int $_b): void {}
}
