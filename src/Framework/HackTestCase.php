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

  private int $numTests = 0;

  public function __construct(
    private string $className = '',
    private vec<ScannedMethod> $methods = vec[],
  ) {
  }

  public async function runAsync(
    (function(string): void) $writeProgress,
  ): Awaitable<dict<string, \Exception>> {
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
        $this->numTests++;
        try {
          if ($type === 'Awaitable') {
            await $instance->$method_name();
          } else {
            $instance->$method_name();
          }
          $writeProgress('.');
        } catch (\Exception $e) {
          if ($e instanceof SkippedTestException) {
            $writeProgress('S');
          } else {
            $writeProgress('F');
          }
          $errors[$method_name] = $e;
        }
      } else if (C\count($providers) > 1) {
        throw new InvalidTestMethodException(
          'There can only be one data provider per test method',
        );
      } else {
        $provider = C\onlyx($providers);
        $tuples = $instance->$provider();
        $this->numTests += C\count($tuples);
        $tuple_num = 0;
        foreach ($tuples as $tuple) {
          $data = '';
          if (C\count($tuple) > 1) {
            $data .= '(';
            foreach ($tuple as $arg) {
              $data .= \var_export($arg, true);
              if ($arg !== C\lastx($tuple)) {
                $data .= ', ';
              }
            }
            $data .= ')';
          } else {
            $data = \var_export(C\onlyx($tuple), true);
          }
          $tuple_num++;
          try {
            if ($type === 'Awaitable') {
              await \call_user_func_array(
                array($instance, $method_name),
                $tuple,
              );
            } else {
              \call_user_func_array(array($instance, $method_name), $tuple);
            }
            $writeProgress('.');
          } catch (\Exception $e) {
            if ($e instanceof SkippedTestException) {
              $writeProgress('S');
            } else {
              $writeProgress('F');
            }
            $errors[$method_name.'.'.$tuple_num.'.'.$data] = $e;
          }
        }
      }
    }

    return $errors;
  }

  public function getNumTests(): int {
    return $this->numTests;
  }

  public function markTestSkipped(string $message): void {
    throw new SkippedTestException($message);
  }

}
