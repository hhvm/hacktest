<?hh // strict
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
use type Facebook\HackTest\HackTestCase;
use HH\Lib\Str;

/**
 * @emails oncall+hack
 */
final class DataProviderTest extends HackTestCase {

  public function provideSimple(): array<mixed> {
    $elements = array('the', 'quick', 'brown', 'fox', 1);
    return $elements;
  }

  /** @dataProvider provideSimple */
  public function testSimple(Traversable<string> $traversable): void {
    expect(Str\join($traversable, '-'))->toBeSame('the-quick-brown-fox-1');
  }
}
