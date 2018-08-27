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
use type Facebook\HackTest\HackTestCase;
use namespace HH\Lib\Str;

/**
 * @emails oncall+hack
 */
final class DataProviderTest extends HackTestCase {

  public function provideSimple(): vec<mixed> {
    $elements = vec['the', 'quick', 'brown', 'fox', 1];
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

  <<DataProvider('provideSimple')>>
  public function testSimple(Traversable<string> $traversable): void {
    expect(Str\join($traversable, '-'))->toBeSame('the-quick-brown-fox-1');
  }

  public function provideMultipleArgs(): vec<mixed> {
    return vec[
      tuple(1, 2),
      tuple(2, 1),
    ];
  }

  <<DataProvider('provideMultipleArgs')>>
  public function testMultipleArgs(int $a, int $b): void {
    expect($a + $b)->toBeSame(3);
  }

  public function provideSkip(): void {
    self::markTestSkipped('This test depends on a data provider that is not ready yet.');
  }

  <<DataProvider('provideSkip')>>
  public function testProviderSkip(int $a): void {}

  <<DataProvider('provideSkip')>>
  public function testProviderSkipDup(int $a): void {}
}
