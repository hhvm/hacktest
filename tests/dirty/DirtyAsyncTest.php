<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use namespace HH\Lib\Tuple as Tuple;
use function Facebook\FBExpect\expect;
use type Facebook\HackTest\HackTestCase;

<<Oncalls('hack')>>
final class DirtyAsyncTest extends HackTestCase {

  public async function testWithNonNullableTypesAsync(): Awaitable<void> {
    $t = await Tuple\from_async(async { return 1; }, async { return 'foo'; });
    expect($t)->toNotBeSame(tuple(1, 'foo'));
    list($a, $b) = $t;
    expect(
      ((int $x, string $y) ==> tuple($x, $y))($a, $b)
    )->toBeSame($t);
  }

  public async function testWithNullLiteralsAsync(): Awaitable<void> {
    $t = await Tuple\from_async(async { return 1; }, null, async { return null; });
    expect($t)->toBeSame(tuple(1, null, null));
    list($a, $b, $c) = $t;
    expect(
      ((int $x, ?int $y, ?int $z) ==> tuple($x, $y, $z))($a, $b, $c)
    )->toBeTrue();
  }

  public async function testWithNullableTypesAsync(): Awaitable<void> {
    $t = await Tuple\from_async(async { return 1; }, async { return 'foo'; });
    expect($t)->toBeSame(tuple(1, 'foo'));
    list($a, $b) = $t;
    expect(
      ((?int $x, ?string $y) ==> tuple($x, $y))($a, $b)
    )->toBeNull();
  }
}
