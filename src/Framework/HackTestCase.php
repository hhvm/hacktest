<?hh
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class HackTestCase {

  public function __construct(private string $className = '', private ?vec<string> $methodNames = null) {
    $this->className = $className;
    $this->methodNames = $methodNames;
  }

  public function run(): void {
    if ($this->methodNames !== null) {
      foreach ($this->methodNames as $method) {
        printf("%s ", $method);
        $instance = new $this->className();
        $instance->$method();
        printf("Passed.\n");
        // TODO: await for async tests
      }
    }
  }
}
