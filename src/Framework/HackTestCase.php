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
    $this->methods = Vec\filter(
      (new \ReflectionClass($this))->getMethods(),
      $method ==> $method->class !== self::class,
    );
    $this->methods = $this->getTestMethods();
  }

  public final async function runAsync(
    (function(TestResult): void) $write_progress,
  ): Awaitable<dict<string, \Throwable>> {
    $errors = dict[];
    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      $doc = $method->getDocComment();
      $providers = vec[];
      if ($doc !== null) {
        $block = new DocBlock((string)$doc);
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
          $write_progress(TestResult::PASSED);
        } catch (\Throwable $e) {
          $this->writeError($e, $write_progress);
          $errors[$method_name] = $e;
        }
      } else if (C\count($providers) > 1) {
        throw new InvalidTestMethodException(
          'There can only be one data provider per test method',
        );
      } else {
        $provider = C\onlyx($providers);
        try {
          /* HH_IGNORE_ERROR[2011] this is unsafe */
          $tuples = $this->$provider();
          if (C\is_empty($tuples)) {
            throw new InvalidDataProviderException(
              'This test depends on a provider that returns no data.',
            );
          }
        } catch (\Throwable $e) {
          $this->numTests++;
          $this->writeError($e, $write_progress);
          $errors[$method_name] = $e;
          continue;
        }
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
            $write_progress(TestResult::PASSED);
          } catch (\Throwable $e) {
            $this->writeError($e, $write_progress);
            $errors[$method_name.'.'.$tuple_num.'.'.$data] = $e;
          }
        }
      }
    }

    return $errors;
  }

  public final function getTestMethods(): vec<\ReflectionMethod> {
    $methods = vec[];
    foreach ($this->methods as $method) {
      if ($method->isPublic() && !$method->isStatic()) {
        $method_name = $method->getName();
        if (Str\starts_with($method_name, 'test')) {
          $type = Str\replace($method->getReturnTypeText(), 'HH\\', '');
          if ($type !== 'void' && $type !== 'Awaitable<void>') {
            throw new InvalidTestMethodException(
              'Test methods must return void or Awaitable<void> (for async methods)',
            );
          }
          $methods[] = $method;
        } else if (!Str\starts_with($method_name, 'provide')) {
          throw new InvalidTestMethodException(
            'Only test methods and data providers can be public',
          );
        }
      }
    }

    return $methods;
  }

  public final function writeError(
    \Throwable $e,
    (function(TestResult): void) $write_progress,
  ): void {
    $status = TestResult::ERROR;
    if ($e instanceof SkippedTestException) {
      $status = TestResult::SKIPPED;
    } else if ($e instanceof \PHPUnit_Framework_ExpectationFailedException) {
      $status = TestResult::FAILED;
    }
    $write_progress($status);
  }

  public final function getNumTests(): int {
    return $this->numTests;
  }

  public final function markTestSkipped(string $message): void {
    throw new SkippedTestException($message);
  }

}
