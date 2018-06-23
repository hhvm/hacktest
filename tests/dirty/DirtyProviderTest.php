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
final class DirtyProviderTest extends HackTestCase {

  public function provideDirtyData(): varray<mixed> {
    $elements = varray['the', 'quicky', 'brown', 'fox', 1];
    return varray[
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

  /** @dataProvider provideDirtyData */
  public function testDirtyData(Traversable<string> $traversable): void {
    expect(Str\join($traversable, '-'))->toBeSame('the-quick-brown-fox-1');
  }

  public function provideNoData(): varray<mixed> {
    return varray[];
  }

  /** @dataProvider provideNoData */
  public function testNoData(int $a): void {
    expect($a)->toBeSame(1);
  }

  /** @dataProvider provideNoData */
  public function testNoDataDup(int $a): void {
    expect($a)->toBeSame(1);
  }

  public function provideError(): varray<mixed> {
    invariant(0 === 1, "This test depends on a provider that throws an error.");
    return varray[
      tuple(1, 2),
      tuple(2, 1)
    ];
  }

  /** @dataProvider provideError */
  public function testProviderError(int $a, int $b): void {}

  /** @dataProvider provideError */
  public function testProviderErrorDup(int $a, int $b): void {}
}
