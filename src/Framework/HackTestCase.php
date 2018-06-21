<?hh // strict
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use type Facebook\HHAPIDoc\DocBlock\DocBlock;
use namespace HH\Lib\{C, Vec, Str};

class HackTestCase {

  private int $numTests = 0;
  private vec<\ReflectionMethod> $methods = vec[];

  public final function __construct() {
    $this->methods = vec((new \ReflectionClass($this))->getMethods())
      |> Vec\filter($$, $method ==> $method->class === static::class)
      |> Vec\filter($$, $method ==> Str\starts_with($method->getName(), 'test'));
  }

  public final async function runAsync(
    (function(string): void) $write_progress,
  ): Awaitable<dict<string, \Exception>> {
    $errors = dict[];
    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      $doc = $method->getDocComment();
      $providers = vec[];
      if ($doc !== null) {
        $block = new DocBlock((string) $doc);
        $providers = $block->getTagsByName('@dataProvider');
      }
      if (C\is_empty($providers)) {
        $this->numTests++;
        try {
          /* HH_IGNORE_ERROR[2011] this is unsafe */
          $res = $this->$method_name();
          if ($res instanceof Awaitable) {
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            await $res;
          }
          $write_progress('.');
        } catch (\Exception $e) {
          if ($e instanceof SkippedTestException) {
            $write_progress('S');
          } else {
            $write_progress('F');
          }
          $errors[$method_name] = $e;
        }
      } else if (C\count($providers) > 1) {
        throw new InvalidTestMethodException(
          'There can only be one data provider per test method',
        );
      } else {
        $provider = C\onlyx($providers);
        /* HH_IGNORE_ERROR[2011] this is unsafe */
        $tuples = $this->$provider();
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
            /* HH_IGNORE_ERROR[2011] this is unsafe */
            $res = $this->$method_name(...$tuple);
            if ($res instanceof Awaitable) {
              /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
              await $res;
            }
            $write_progress('.');
          } catch (\Exception $e) {
            if ($e instanceof SkippedTestException) {
              $write_progress('S');
            } else {
              $write_progress('F');
            }
            $errors[$method_name.'.'.$tuple_num.'.'.$data] = $e;
          }
        }
      }
    }

    return $errors;
  }

  public final function getNumTests(): int {
    return $this->numTests;
  }

  public final function markTestSkipped(string $message): void {
    throw new SkippedTestException($message);
  }

}
