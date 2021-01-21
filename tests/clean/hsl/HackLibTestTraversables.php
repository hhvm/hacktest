<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// FIXME: Temporarily reverting to the version from before
// https://github.com/hhvm/hsl/commit/5d23a53e6afae03872b96954e6b11437e894e63e
// because HackLibTestForwardOnlyIterator needs context declarations that can't
// be made compatible with all HHVM versions currently supported by Hacktest.
abstract final class HackLibTestTraversables {

  // For testing functions that accept Traversables
  public static function getIterator<T>(Traversable<T> $ary): Iterator<T> {
    foreach ($ary as $v) {
      yield $v;
    }
  }

  // For testing functions that accept KeyedTraversables
  public static function getKeyedIterator<Tk, Tv>(
    KeyedTraversable<Tk, Tv> $ary,
  ): KeyedIterator<Tk, Tv> {
    foreach ($ary as $k => $v) {
      yield $k => $v;
    }
  }
}
