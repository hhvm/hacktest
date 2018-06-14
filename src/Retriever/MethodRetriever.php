<?hh  // strict
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use type Facebook\DefinitionFinder\{
  ScannedClass,
  ScannedMethod,
  FileParser,
};
use function Facebook\FBExpect\expect;
use HH\Lib\Str;

class MethodRetriever {

  private vec<ScannedMethod> $test_methods;

  public function __construct(private ScannedClass $sbc) {
    $this->test_methods = vec[];
    $methods = $this->sbc->getMethods();
    foreach ($methods as $method) {
      $method_name = $method->getName();
      // don't worry about private/protected methods
      if ($method->isPublic()) {
        if (!Str\starts_with($method_name, 'test')) {
          if (!Str\starts_with($method_name, 'provide')) {
            throw
              new InvalidTestMethodException('Only test methods and data providers can be public');
          }
          continue;
        }
        $type = $method->getReturnType()?->getTypeText();
        if ($type !== 'void' && $type !== 'Awaitable<void>') {
          throw new InvalidTestMethodException(
            'Test methods must return void or Awaitable<void> (for async methods)',
          );
        }
        $this->test_methods[] = $method;
      }
    }
  }

  public function getTestMethods(): vec<ScannedMethod> {
    return $this->test_methods;
  }

  public function getTestMethodNames(): vec<string> {
    $method_names = vec[];
    foreach ($this->test_methods as $method) {
      $method_names[] = $method->getName();
    }
    return $method_names;
  }
}
