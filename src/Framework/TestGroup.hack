/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use namespace HH\Lib\C;

/** Mark a test as a member of a particular group.
 *
 *
 * @example
 *
 *     <<TestGroup('quick')>>
 *     public function testFoo(): void {
 *     }
 */
final class TestGroup implements \HH\MethodAttribute {
  private keyset<string> $groups;

  public function __construct(string ...$groups) {
    $this->groups = keyset($groups);
  }

  public function contains(string $group): bool {
    return C\contains_key($this->groups, $group);
  }
}
