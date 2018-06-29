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
  ): Awaitable<dict<string, ?\Throwable>> {
    $errors = dict[];
    $to_run = dict[];
    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      $doc = $method->getDocComment();
      $providers = vec[];
      if ($doc !== null) {
        $block = new DocBlock((string)$doc);
        $providers = $block->getTagsByName('@dataProvider');
      }
      if (C\is_empty($providers)) {
        /* HH_IGNORE_ERROR[2011] this is unsafe */
        $to_run[$method_name] = () ==> $this->$method_name();
        continue;
      }
      if (C\count($providers) > 1) {
        throw new InvalidTestMethodException(
          Str\format('There can only be one data provider in %s', $method_name),
        );
      }
      $provider = C\onlyx($providers);
      try {
        /* HH_IGNORE_ERROR[2011] this is unsafe */
        $tuples = $this->$provider();
        if (C\is_empty($tuples)) {
          throw new InvalidDataProviderException(
            Str\format(
              'This test depends on a provider (%s) that returns no data.',
              $provider,
            ),
          );
        }
      } catch (\Throwable $e) {
        $this->writeError($e, $write_progress);
        $errors[$method_name] = $e;
        continue;
      }
      $tuple_num = 0;
      foreach ($tuples as $tuple) {
        $tuple_num++;
        $key = Str\format(
          '%s.%d.%s',
          $method_name,
          $tuple_num,
          $this->prettyFormat($tuple),
        );
        /* HH_IGNORE_ERROR[2011] this is unsafe */
        $to_run[$key] = () ==> $this->$method_name(...$tuple);
      }
    }

    foreach ($to_run as $key => $runnable) {
      try {
        $res = $runnable();
        if ($res instanceof Awaitable) {
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $res;
        }
        $write_progress(TestResult::PASSED);
        $errors[$key] = null;
      } catch (\Throwable $e) {
        $errors[$key] = $e;
        $this->writeError($e, $write_progress);
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
              Str\format(
                'Test method (%s) must return void or Awaitable<void> (for async methods)',
                $method_name,
              ),
            );
          }
          $methods[] = $method;
        } else if (!Str\starts_with($method_name, 'provide')) {
          throw new InvalidTestMethodException(
            Str\format(
              'Only test methods and data providers can be public. Consider changing %s to a private or protected method.',
              $method_name,
            ),
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
    } else if (
      \is_a($e, 'PHPUnit\\Framework\\ExpectationFailedException', true) ||
      \is_a($e, 'PHPUnit_Framework_ExpectationFailedException', true)
    ) {
      $status = TestResult::FAILED;
    }
    $write_progress($status);
  }

  public final function prettyFormat(Container<mixed> $tuple): string {
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

    return $data;
  }

  public final function markTestSkipped(string $message): void {
    throw new SkippedTestException($message);
  }

}
