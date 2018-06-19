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
use type Facebook\HHAPIDoc\DocBlock\DocBlock;
use namespace HH\Lib\C;

class HackTestCase {

  private static string $output = '';

  public function __construct(
    private string $className = '',
    private vec<ScannedMethod> $methods = vec[],
  ) {
  }

  public async function runAsync(): Awaitable<dict<string, mixed>> {
    $errors = dict[];
    $results = dict[];
    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      $instance = new $this->className();
      $doc = $method->getDocComment();
      $providers = vec[];
      if ($doc !== null) {
        $block = new DocBlock($doc);
        $providers = $block->getTagsByName('@dataProvider');
      }
      try {
        if (C\is_empty($providers)) {
          $results[$method_name] = $instance->$method_name();
        } else {
          $args = array();
          foreach ($providers as $provider) {
            $arg = $instance->$provider();
            $args[] = $arg;
          }
          $results[$method_name] = \call_user_func_array(array($instance, $method_name), $args);
        }
      } catch (\Exception $e) {
        $errors[$method_name] = $e;
      }
    }

    foreach ($results as $method_name => $result) {
      try {
        if ($result instanceof Awaitable) {
          await $result;
        }
        $errors[$method_name] = 'Passed';
        self::$output .= '.';
      } catch (\Exception $e) {
        $errors[$method_name] = $e;
        self::$output .= 'F';
      }
    }

    return $errors;
  }

  public static function getOutput(): string {
    return self::$output;
  }

}
