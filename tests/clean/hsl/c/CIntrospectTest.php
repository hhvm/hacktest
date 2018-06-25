<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use namespace HH\Lib\C;
use function Facebook\FBExpect\expect;
use type Facebook\HackTest\HackTestCase;

/**
 * @emails oncall+hack
 */
final class CIntrospectTest extends HackTestCase {

  public static function provideTestAny(): vec<mixed> {
    return vec[
      tuple(
        Vector {2, 4, 6, 8, 9, 10, 12},
        $v ==> $v % 2 === 1,
        true,
      ),
      tuple(
        Vector {2, 4, 6, 8, 10, 12},
        $v ==> $v % 2 === 1,
        false,
      ),
    ];
  }

  /** @dataProvider provideTestAny */
  public function testAny<T>(
    Traversable<T> $traversable,
    (function(T): bool) $predicate,
    bool $expected,
  ): void {
    expect(C\any($traversable, $predicate))->toBeSame($expected);
  }

  public static function provideTestAnyWithoutPredicate(): vec<mixed> {
    return vec[
      tuple(
        vec[],
        false,
      ),
      tuple(
        vec[null, 0, '0', ''],
        false,
      ),
      tuple(
        HackLibTestTraversables::getIterator(
          vec[null, 0, '0', '', 1],
        ),
        true,
      ),
    ];
  }

  /** @dataProvider provideTestAnyWithoutPredicate */
  public function testAnyWithoutPredicate<T>(
    Traversable<T> $traversable,
    bool $expected,
  ): void {
    expect(C\any($traversable))->toBeSame($expected);
  }

  public static function provideTestContains(): vec<mixed> {
    return vec[
      tuple(
        vec[1, 2, 3, 4, 5],
        3,
        true,
      ),
      tuple(
        vec[1, 2, '3', 4, 5],
        3,
        false,
      ),
      tuple(
        keyset[1, 2, 3, 4, 5],
        3,
        true,
      ),
      tuple(
        keyset[1, 2, '3', 4, 5],
        3,
        false,
      ),
      tuple(
        HackLibTestTraversables::getIterator(range(1, 5)),
        3,
        true,
      ),
      tuple(
        vec[dict[1 => 2, 3 => 4]],
        dict[1 => 2, 3 => 4],
        true,
      ),
      tuple(
        vec[vec[3]],
        vec[4],
        false,
      ),
    ];
  }

  /** @dataProvider provideTestContains */
  public function testContains<T>(
    Traversable<T> $traversable,
    T $value,
    bool $expected,
  ): void {
    expect(C\contains($traversable, $value))->toBeSame($expected);
  }

  public static function provideTestContainsKey(): vec<mixed> {
    return vec[
      tuple(
        darray['3' => 3],
        3,
        true,
      ),
      tuple(
        dict['3' => 3],
        3,
        false,
      ),
      tuple(
        dict[],
        3,
        false,
      ),
      tuple(
        Map {'foo' => 'bar'},
        'bar',
        false,
      ),
      tuple(
        Vector {'the', 'quick', 'brown', 'fox'},
        4,
        false,
      ),
      tuple(
        Vector {'the', 'quick', 'brown', 'fox'},
        0,
        true,
      ),
    ];
  }

  /** @dataProvider provideTestContainsKey */
  public function testContainsKey<Tk, Tv>(
    KeyedContainer<Tk, Tv> $container,
    Tk $key,
    bool $expected,
  ): void {
    expect(C\contains_key($container, $key))->toBeSame($expected);
  }

  public static function provideTestCount(): vec<mixed> {
    return vec[
      tuple(vec[], 0),
      tuple(range(1, 10), 10),
      tuple(Set {1, 2}, 2),
      tuple(Vector {1, 2}, 2),
      tuple(Map {'foo' => 'bar', 'baz' => 'bar2'}, 2),
      tuple(keyset[1, 2, 3], 3),
      tuple(vec[1, 2, 3], 3),
      tuple(dict['foo' => 'bar', 'baz' => 'bar2'], 2),
    ];
  }

  /** @dataProvider provideTestCount */
  public function testCount<T>(
    Container<T> $container,
    int $expected,
  ): void {
    expect(C\count($container))->toBeSame($expected);
  }

  public static function provideTestEvery(): vec<mixed> {
    return vec[
      tuple(
        Vector {2, 4, 6, 8, 9, 10, 12},
        $v ==> $v % 2 === 0,
        false,
      ),
      tuple(
        Vector {2, 4, 6, 8, 10, 12},
        $v ==> $v % 2 === 0,
        true,
      ),
    ];
  }

  /** @dataProvider provideTestEvery */
  public function testEvery<T>(
    Traversable<T> $traversable,
    (function(T): bool) $predicate,
    bool $expected,
  ): void {
    expect(C\every($traversable, $predicate))->toBeSame($expected);
  }

  public static function provideTestEveryWithoutPredicate(): vec<mixed> {
    return vec[
      tuple(
        vec[],
        true,
      ),
      tuple(
        HackLibTestTraversables::getIterator(range(1, 5)),
        true,
      ),
    ];
  }

  /** @dataProvider provideTestEveryWithoutPredicate */
  public function testEveryWithoutPredicate<T>(
    Traversable<T> $traversable,
    bool $expected,
  ): void {
    expect(C\every($traversable))->toBeSame($expected);
  }

  public static function provideTestIsEmpty(): vec<mixed> {
    return vec[
      tuple(vec[], true),
      tuple(vec[1], false),
      tuple(darray['foo' => 'bar'], false),
      tuple(dict[], true),
      tuple(dict['foo' => 'bar'], false),
      tuple(vec[], true),
      tuple(vec[1], false),
      tuple(keyset[], true),
      tuple(keyset[1], false),
      tuple(Map {}, true),
      tuple(Map {'foo' => 'bar'}, false),
      tuple(Vector {}, true),
      tuple(Vector {1}, false),
      tuple(Set {}, true),
      tuple(Set {1}, false),
    ];
  }

  /** @dataProvider provideTestIsEmpty */
  public function testIsEmpty<T>(
    Container<T> $container,
    bool $expected,
  ): void {
    expect(C\is_empty($container))->toBeSame($expected);
  }
}
