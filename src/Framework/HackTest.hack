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
/* HHAST_IGNORE_ALL[DontAwaitInALoop] */

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

  <<__LateInit>> private string $filename;

  public final function __construct() {
    $class = new \ReflectionClass($this);
    $this->filename = $class->getFileName() as string;
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

  const type TFilters = shape(
    'methods' => (function(string): string),
    // TODO: dataproviders
  );

  public final async function runTestsAsync(
    (function(\ReflectionMethod): bool) $method_filter,
    (function(ProgressEvent): Awaitable<void>) $progress_callback,
  ): Awaitable<void> {
    $progress = new _Private\Progress(
      $progress_callback,
      $this->filename,
      static::class,
    );

    await static::beforeFirstTestAsync();
    await using new _Private\OnScopeExitAsync(
      async () ==> await static::afterLastTestAsync(),
    );

    foreach ($this->methods as $method) {
      $to_run = vec[];
      if (!$method_filter($method)) {
        continue;
      }

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
      $provider = $method->getAttributeClass(DataProvider::class)?->provider;
      if ($provider is nonnull) {
        $providers[] = $provider;
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
        await $progress->invokingDataProviderAsync($method_name);
        $provider = C\onlyx($providers);
        await $this->beforeEachTestAsync();
        $this->setUpNeeded = false;
        try {
          if (Str\contains($provider, '::')) {
            list($class, $method) = Str\split($provider, '::', 2);
            $rm = new \ReflectionMethod($class, $method);
            $tuples = $rm->invoke(null);
          } else {
            $rm = new \ReflectionMethod($this, $provider);
            if ($rm->isStatic()) {
              $tuples = $rm->invoke(null);
            } else {
              $tuples = $rm->invoke($this);
            }
          }
          if ($tuples is Awaitable<_>) {
            $tuples = await $tuples;
          }
          $tuples = $tuples as KeyedContainer<_, _>;
          if (C\is_empty($tuples)) {
            throw new InvalidDataProviderException(
              Str\format(
                'This test depends on a provider (%s) that returns no data.',
                $provider,
              ),
            );
          }
        } catch (\Throwable $e) {
          await $this->afterEachTestAsync();
          await $progress->testFinishedWithExceptionAsync($provider, null, $e);
          continue;
        }

        foreach ($tuples as $idx => $tuple) {
          $tuple = vec($tuple as Traversable<_>);
          $to_run[] = tuple(
            $method_name,
            tuple($idx as arraykey, $tuple as Container<_>),
            /* HH_IGNORE_ERROR[2011] this is unsafe */
            () ==> $this->$method_name(...$tuple),
          );
        }
      }

      foreach ($to_run as list($method, $data_provider_row, $runnable)) {
        await $progress->testStartingAsync($method, $data_provider_row);
        if ($this->setUpNeeded) {
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
            await $res;
          }
          /* HH_IGNORE_ERROR[6002] this is used in catch block */
          $clean = true;
          await $this->afterEachTestAsync();
          if ($this->expectedException !== null) {
            throw new ExpectationFailedException(
              Str\format(
                'Failed asserting that %s was thrown',
                $this->expectedException,
              ),
            );
          }
          await $progress->testPassedAsync($method, $data_provider_row);
        } catch (\Throwable $e) {
          if (!$clean) {
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
            await $progress->testPassedAsync($method, $data_provider_row);
          } else {
            await $progress->testFinishedWithExceptionAsync(
              $method,
              $data_provider_row,
              $e,
            );
          }
        }
      }
    }
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

  public static final function markTestSkipped(string $message): noreturn {
    throw new SkippedTestException($message);
  }

  public static function markTestIncomplete(string $message): noreturn {
    throw new SkippedTestException($message);
  }

  public static final function fail(string $message = ''): noreturn {
    throw new \RuntimeException($message);
  }

  public final function setExpectedException(
    string $exception,
    ?string $exception_message = '',
    mixed $exception_code = null,
  ): void {
    $this->expectedException = $exception;
    $this->expectedExceptionMessage = $exception_message;
    $this->expectedExceptionCode = static::computeExpectedExceptionCode(
      $exception_code,
    );
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
