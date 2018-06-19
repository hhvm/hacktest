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

  const TEST_PASSED = 'PASSED';

  public function __construct(
    private string $className = '',
    private vec<ScannedMethod> $methods = vec[],
  ) {
  }

  public async function runAsync(): Awaitable<dict<string, mixed>> {
    $errors = dict[];
    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      $instance = new $this->className();
      $doc = $method->getDocComment();
      $providers = vec[];
      if ($doc !== null) {
        $block = new DocBlock($doc);
        $providers = $block->getTagsByName('@dataProvider');
      }
      $type = $method->getReturnType()?->getTypeName();

      if (C\is_empty($providers)) {
        try {
          if ($type === 'Awaitable') {
            await $instance->$method_name();
          } else {
            $instance->$method_name();
          }
          $errors[$method_name] = self::TEST_PASSED;
        } catch (\Exception $e) {
          $errors[$method_name] = $e;
        }
      } else if (C\count($providers) > 1) {
        throw new InvalidTestMethodException(
          'There can only be one data provider per test method',
        );
      } else {
        $provider = C\firstx($providers);
        $tuples = $instance->$provider();
        $tuple_num = 0;
        foreach ($tuples as $tuple) {
          $tuple_num++;
          // TODO: display this in test output?
          $data = \var_export(C\firstx($tuple), true);
          try {
            if ($type === 'Awaitable') {
              await \call_user_func_array(array($instance, $method_name), $tuple);
            } else {
              \call_user_func_array(array($instance, $method_name), $tuple);
            }
            $errors[$method_name.'.'.$tuple_num] = self::TEST_PASSED;
          } catch (\Exception $e) {
            $errors[$method_name.'.'.$tuple_num] = $e;
          }
        }
      }
    }

    return $errors;
  }
}
