/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// @lint-ignore-every NAMESPACES

namespace Facebook\HackTest;

/**
 * Identifies the method that provides data to a test method.
 *
 *  Used on 'testFoo' methods in HackTest classes.
 *
 * @example
 *
 *   class MyClassTest extends HackTest {
 *     public function fooData(): vec<(string, int)> {
 *       return vec[
 *         tuple('foo', 123),
 *         tuple('bar', 456),
 *       ];
 *     }
 *
 *     <<DataProvider('fooData')>>
 *     public function testFoo(string $arg1, int $arg2) {
 *       // code
 *     }
 *   }
 *
 */
final class DataProvider implements \HH\MethodAttribute {
  public function __construct(
    /** The name of a public method providing parameters for a test */
    public string $provider,
  ) {
  }
}
