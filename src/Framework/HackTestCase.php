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

  public function run(): vec<\Exception> {
    $errors = vec[];

    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      $instance = new $this->className();
      $doc = $method->getDocComment();
      $providers = null;
      if ($doc !== null) {
        $block = new DocBlock($doc);
        $providers = $block->getTagsByName('@dataProvider');
      }
      try {
        if ($providers === null) {
          $instance->$method_name();
        } else {
          $provider = C\firstx($providers);
          $data = $instance->$provider();
          $instance->$method_name($data);
        }
        \printf(".");
      } catch (\Exception $e) {
        \printf("F");
        $errors[] = $e;
      }
    }

    return $errors;
  }
}
