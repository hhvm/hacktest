<?hh // strict
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// @lint-ignore-every NAMESPACES
// @lint-ignore-every HackLint5583 await in loop for tests

namespace Facebook\HackTest;

use type Facebook\HHAPIDoc\DocBlock\DocBlock;
use namespace HH\Lib\{C, Str, Vec};

class HackTestCase {

  private vec<\ReflectionMethod> $methods = vec[];

  private ?string $expectedException = null;
  private ?string $expectedExceptionMessage = null;
  private ?int $expectedExceptionCode = null;
  private bool $setUpNeeded = true;
  const bool ALLOW_STATIC_TEST_METHODS = false;

  public final function __construct() {
    $class = new \ReflectionClass($this);
    $this->methods = Vec\filter(
      $class->getMethods(),
      $method ==> Str\starts_with($method->getName(), 'test'),
    );
    $this->filterTestMethods();
    $this->validateTestMethods();
  }

  public final async function runAsync(
    (function(TestResult): void) $write_progress,
  ): Awaitable<dict<string, ?\Throwable>> {

    $errors = dict[];
    await static::beforeFirstTest();

    foreach ($this->methods as $method) {
      $to_run = dict[];

      $this->clearExpectedException();
      $exception = $method->getAttribute('ExpectedException');
      if ($exception !== null) {
        $exception_message = $method->getAttribute('ExpectedExceptionMessage');
        $msg = null;
        $code = null;
        if ($exception_message !== null) {
          $msg = (string)C\onlyx($exception_message);
        }
        $exception_code = $method->getAttribute('ExpectedExceptionCode');
        if ($exception_code !== null) {
          $code = (string)C\onlyx($exception_code);
        }
        $this->setExpectedException((string)C\onlyx($exception), $msg, $code);
      }

      $doc = $method->getDocComment();
      $providers = vec[];
      if ($doc !== false) {
        $block = new DocBlock((string)$doc);
        $providers = $block->getTagsByName('@dataProvider');
      }
      $provider = $method->getAttribute('DataProvider');
      if ($provider !== null) {
        $providers[] = (string)C\onlyx($provider);
      }

      $method_name = $method->getName();
      if (C\is_empty($providers)) {
        /* HH_IGNORE_ERROR[2011] this is unsafe */
        $to_run[$method_name] = () ==> $this->$method_name();
      } else {
        if (C\count($providers) > 1) {
          throw new InvalidTestMethodException(
            Str\format(
              'There can only be one data provider in %s',
              $method_name,
            ),
          );
        }
        $provider = C\onlyx($providers);
        /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
        await $this->beforeEachTest();
        $this->setUpNeeded = false;
        try {
          if (Str\contains($provider, '::')) {
            /* HH_IGNORE_ERROR[4009] this is unsafe */
            $tuples = $provider();
          } else {
            /* HH_IGNORE_ERROR[2011] this is unsafe */
            $tuples = $this->$provider();
          }
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
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $this->afterEachTest();
          continue;
        }

        $tuple_num = 0;
        foreach ($tuples as $tuple) {
          $tuple as Container<_>;
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
        if ($this->setUpNeeded) {
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $this->beforeEachTest();
        } else {
          $this->setUpNeeded = true;
        }
        $clean = false;
        try {
          $res = $runnable();
          if ($res instanceof Awaitable) {
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            await $res;
          }
          /* HH_IGNORE_ERROR[6002] this is used in catch block */
          $clean = true;
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $this->afterEachTest();
          if ($this->expectedException !== null) {
            throw new ExpectationFailedException(
              Str\format(
                'Failed asserting that %s was thrown',
                $this->expectedException,
              ),
            );
          }
          $write_progress(TestResult::PASSED);
          $errors[$key] = null;
        } catch (\Throwable $e) {
          if (!$clean) {
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            await $this->afterEachTest();
          }
          $pass = false;
          if (
            $this->expectedException !== null &&
            !($e instanceof SkippedTestException) &&
            \is_a($e, $this->expectedException)
          ) {
            $pass = true;
            $message = (string)$e->getMessage();
            $expected_message = (string)$this->expectedExceptionMessage;
            if (!Str\contains($message, $expected_message)) {
              $e = new ExpectationFailedException(
                Str\format(
                  'Failed asserting that the exception message \'%s\' contains \'%s\'',
                  $e->getMessage(),
                  $expected_message,
                ),
              );
              $pass = false;
            } else if (
              $this->expectedExceptionCode !== null &&
              $this->expectedExceptionCode !== $e->getCode()
            ) {
              $exception_code = (int)$this->expectedExceptionCode;
              $e = new ExpectationFailedException(
                Str\format(
                  'Failed asserting that the exception code %d is equal to %d',
                  (int)$e->getCode(),
                  $exception_code,
                ),
              );
              $pass = false;
            }
          }
          if ($pass) {
            $write_progress(TestResult::PASSED);
            $errors[$key] = null;
          } else {
            $errors[$key] = $e;
            $this->writeError($e, $write_progress);
          }
        }
      }
    }
    await static::afterLastTest();

    return $errors;
  }

  private final function filterTestMethods(): void {
    $methods = vec[];
    foreach ($this->methods as $method) {
      $type_text = $method->getReturnTypeText();
      if ($type_text === false) {
        // nothing we can really do if a method begins with 'test' and has no return type hint
        $methods[] = $method;
        continue;
      }
      $type = Str\replace($type_text, 'HH\\', '');
      if ($type === 'void' || $type === 'Awaitable<void>') {
        $methods[] = $method;
      }
    }
    $this->methods = $methods;
  }

  private final function validateTestMethods(): void {
    foreach ($this->methods as $method) {
      $method_name = $method->getName();
      if (!$method->isPublic()) {
        throw new InvalidTestMethodException(
          Str\format('Test method (%s) must be public', $method_name),
        );
      }
      if (!static::ALLOW_STATIC_TEST_METHODS && $method->isStatic()) {
        throw new InvalidTestMethodException(
          Str\format('Test method (%s) cannot be static', $method_name),
        );
      }
    }
  }

  private final function writeError(
    \Throwable $e,
    (function(TestResult): void) $write_progress,
  ): void {
    $status = TestResult::ERROR;
    if ($e instanceof SkippedTestException) {
      $status = TestResult::SKIPPED;
    } else if (
      \is_a($e, 'PHPUnit\\Framework\\ExpectationFailedException') ||
      \is_a($e, 'PHPUnit_Framework_ExpectationFailedException') ||
      $e instanceof ExpectationFailedException
    ) {
      $status = TestResult::FAILED;
    }
    $write_progress($status);
  }

  private final function prettyFormat(Container<mixed> $tuple): string {
    $data = '';
    $size = C\count($tuple);
    $num_arg = 1;
    if ($size > 1) {
      $data .= '(';
      foreach ($tuple as $arg) {
        $data .= \var_export($arg, true);
        if ($num_arg++ !== $size) {
          $data .= ', ';
        }
      }
      $data .= ')';
    } else {
      $data = \var_export(C\onlyx($tuple), true);
    }

    return $data;
  }

  public static final function markTestSkipped(string $message): void {
    throw new SkippedTestException($message);
  }

  public static function markTestIncomplete(string $message): void {
    throw new SkippedTestException($message);
  }

  public static final function fail(string $message = ''): void {
    throw new \RuntimeException($message);
  }

  public final function setExpectedException(
    string $exception,
    ?string $exception_message = '',
    mixed $exception_code = null,
  ): void {
    $this->expectedException = $exception;
    $this->expectedExceptionMessage = $exception_message;
    $this->expectedExceptionCode = static::computeExpectedExceptionCode($exception_code);
  }

  private function clearExpectedException(): void {
    $this->expectedException = null;
    $this->expectedExceptionMessage = null;
    $this->expectedExceptionCode = null;
  }

  public static function computeExpectedExceptionCode(mixed $exception_code): ?int {
    if ($exception_code is int) {
      return $exception_code;
    }
    if (PHP\ctype_digit($exception_code)) {
      return (int)$exception_code;
    }

    // can't handle arbitrary enums for open source
    return null;
  }

  public async function beforeEachTest(): Awaitable<void> {}
  public async function afterEachTest(): Awaitable<void> {}
  public static async function beforeFirstTest(): Awaitable<void> {}
  public static async function afterLastTest(): Awaitable<void> {}

}
