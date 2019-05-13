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

use namespace HH\Lib\{C, Str, Vec};

<<__ConsistentConstruct>>
class HackTest {

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

  final public function getTestMethods(): vec<\ReflectionMethod> {
    return $this->methods;
  }

  public final async function runTestsAsync(
    (function(
      classname<HackTest>,
      ?string,
      ?arraykey,
      TestProgressEvent,
    ): Awaitable<void>) $progress_writer,
    (function(TestResult): Awaitable<void>) $result_writer,
  ): Awaitable<dict<string, ?\Throwable>> {

    $errors = dict[];
    await static::beforeFirstTestAsync();

    foreach ($this->methods as $method) {
      $to_run = vec[];

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

      $providers = vec[];
      $provider = $method->getAttribute('DataProvider');
      if ($provider !== null) {
        $providers[] = (string)C\onlyx($provider);
      }

      $method_name = $method->getName();
      if (C\is_empty($providers)) {
        /* HH_IGNORE_ERROR[2011] this is unsafe */
        $to_run[] = tuple($method_name, null, () ==> $this->$method_name());
      } else {
        if (C\count($providers) > 1) {
          throw new InvalidTestMethodException(
            Str\format(
              'There can only be one data provider in %s',
              $method_name,
            ),
          );
        }
      /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
        await $progress_writer(
          static::class,
          $method_name,
          null,
          TestProgressEvent::CALLING_DATAPROVIDERS,
        );
        $provider = C\onlyx($providers);
        /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
        await $this->beforeEachTestAsync();
        $this->setUpNeeded = false;
        try {
          if (Str\contains($provider, '::')) {
            /* HH_IGNORE_ERROR[4009] this is unsafe */
            $tuples = $provider();
          } else {
            /* HH_IGNORE_ERROR[2011] this is unsafe */
            $rm = new \ReflectionMethod($this, $provider);
            if ($rm->isStatic()) {
              $tuples = $rm->invoke(null);
            } else {
              $tuples = $rm->invoke($this);
            }
          }
          if ($tuples is Awaitable<_>) {
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            $tuples = await $tuples;
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
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $this->writeErrorAsync($e, $result_writer);
          $errors[$method_name] = $e;
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $this->afterEachTestAsync();
          continue;
        }

        foreach ($tuples as $idx => $tuple) {
          $tuple = vec($tuple);
          $to_run[] = tuple(
            $method_name,
            $idx,
          /* HH_IGNORE_ERROR[2011] this is unsafe */
            () ==> $this->$method_name(...$tuple),
          );
        }
      }

      foreach ($to_run as list($method, $data_provider_key, $runnable)) {
        $key = $method;
        if ($data_provider_key is nonnull) {
          $key .= '['.$data_provider_key.']';
        }
        $p = async (TestProgressEvent $event) ==> await $progress_writer(
          static::class,
          $method,
          $data_provider_key,
          $event,
        );
        /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
        await $p(TestProgressEvent::STARTING);
        if ($this->setUpNeeded) {
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $this->beforeEachTestAsync();
        } else {
          $this->setUpNeeded = true;
        }
        if ($exception === null) {
          $this->clearExpectedException();
        }
        $clean = false;
        try {
          $res = $runnable();
          if ($res is Awaitable<_>) {
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            await $res;
          }
          /* HH_IGNORE_ERROR[6002] this is used in catch block */
          $clean = true;
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $this->afterEachTestAsync();
          if ($this->expectedException !== null) {
            throw new ExpectationFailedException(
              Str\format(
                'Failed asserting that %s was thrown',
                $this->expectedException,
              ),
            );
          }
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $result_writer(TestResult::PASSED);
          $errors[$key] = null;
        } catch (\Throwable $e) {
          if (!$clean) {
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            await $this->afterEachTestAsync();
          }
          $pass = false;
          if (
            $this->expectedException !== null &&
            !($e is SkippedTestException) &&
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
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            await $result_writer(TestResult::PASSED);
            $errors[$key] = null;
          } else {
            $errors[$key] = $e;
            /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
            await $this->writeErrorAsync($e, $result_writer);
          }
        } finally {
          /* HHAST_IGNORE_ERROR[DontAwaitInALoop] */
          await $p(TestProgressEvent::FINISHED);
        }
      }
    }
    await static::afterLastTestAsync();

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

  private final async function writeErrorAsync(
    \Throwable $e,
    (function(TestResult): Awaitable<void>) $result_writer,
  ): Awaitable<void> {
    $status = TestResult::ERROR;
    if ($e is SkippedTestException) {
      $status = TestResult::SKIPPED;
    } else if (
      \is_a($e, 'PHPUnit\\Framework\\ExpectationFailedException') ||
      \is_a($e, 'PHPUnit_Framework_ExpectationFailedException') ||
      $e is ExpectationFailedException
    ) {
      $status = TestResult::FAILED;
    }
    await $result_writer($status);
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
    $this->expectedExceptionCode =
      static::computeExpectedExceptionCode($exception_code);
  }

  private function clearExpectedException(): void {
    $this->expectedException = null;
    $this->expectedExceptionMessage = null;
    $this->expectedExceptionCode = null;
  }

  public static function computeExpectedExceptionCode(
    mixed $exception_code,
  ): ?int {
    if ($exception_code is int) {
      return $exception_code;
    }
    if (!($exception_code is string)) {
      return null;
    }
    $int = Str\to_int($exception_code);
    if ($int !== null) {
      return $int;
    }

    // can't handle arbitrary enums for open source
    return null;
  }

  public async function beforeEachTestAsync(): Awaitable<void> {}
  public async function afterEachTestAsync(): Awaitable<void> {}
  public static async function beforeFirstTestAsync(): Awaitable<void> {}
  public static async function afterLastTestAsync(): Awaitable<void> {}

}
