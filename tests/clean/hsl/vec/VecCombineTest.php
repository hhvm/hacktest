<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use namespace HH\Lib\Vec;
use function Facebook\FBExpect\expect;
use type Facebook\HackTest\HackTestCase;

<<Oncalls('hack')>>
final class VecCombineTest extends HackTestCase {

  public static function provideTestConcat(): vec<mixed> {
    return vec[
      tuple(
        vec[],
        vec[],
        vec[],
      ),
      tuple(
        vec[],
        vec[
          darray[], Vector {}, Map {}, Set {},
        ],
        vec[],
      ),
      tuple(
        vec['the', 'quick'],
        vec[
          Vector {'brown', 'fox'},
          Map {'jumped' => 'over'},
          HackLibTestTraversables::getIterator(vec['the', 'lazy', 'dog']),
        ],
        vec['the', 'quick', 'brown', 'fox', 'over', 'the', 'lazy', 'dog'],
      ),
    ];
  }

  <<DataProvider('provideTestConcat')>>
  public function testConcat<Tv>(
    Traversable<Tv> $first,
    Container<Traversable<Tv>> $rest,
    vec<Tv> $expected,
  ): void {
    expect(Vec\concat($first, ...$rest))->toBeSame($expected);
  }

}
