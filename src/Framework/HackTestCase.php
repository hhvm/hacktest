<?hh
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;
use type Facebook\DefinitionFinder\ScannedMethod;
use HH\Lib\C;

class HackTestCase {

  public function __construct(private string $className = '', private vec<ScannedMethod> $methods = vec[]) {
  }

  public async function runAsync(): Awaitable<dict<string, mixed>> {
    $errors = dict[];

    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      $key = $this->className.'.'.$method_name;
      $instance = new $this->className();
      $doc = $method->getDocComment();
      $providers = null;
      if ($doc !== null) {
        $block = new DocBlock($doc);
        $providers = $block->getTagsByName('@dataProvider');
      }
      $type = $method->getReturnType()?->getTypeName();
      try {
        if ($providers === null) {
          if ($type === 'Awaitable') {
            await $instance->$method_name();
          } else {
            $instance->$method_name();
          }
        } else {
          $provider = C\firstx($providers);
          $data = $instance->$provider();
          if ($type === 'Awaitable') {
            await $instance->$method_name($data);
          } else {
            $instance->$method_name($data);
          }
        }
        \printf(".");
        $errors[$key] = 'Passed';
      } catch (\Exception $e) {
        \printf("F");
        $errors[$key] = $e;
      }
    }
    \printf("\n\n");

    return $errors;
  }
}
