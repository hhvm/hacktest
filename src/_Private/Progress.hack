/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest\_Private;

use namespace Facebook\HackTest;

final class Progress {
  private vec<HackTest\ErrorProgressEvent> $errors = vec[];

  public function __construct(
    private (function(HackTest\ProgressEvent): Awaitable<void>) $callback,
    private string $path,
    private classname<HackTest\HackTest> $class,
  ) {
  }

  public async function invokingDataProviderAsync(
    string $test_method,
  ): Awaitable<void> {
    $ev = new HackTest\InvokingDataProvidersProgressEvent(
      $this->path,
      $this->class,
      $test_method,
    );
    $cb = $this->callback;
    await $cb($ev);
  }

  public async function testStartingAsync(
    string $test_method,
    ?(arraykey, Container<mixed>) $data_provider_row,
  ): Awaitable<void> {
    $ev = new HackTest\TestStartingProgressEvent(
      $this->path,
      $this->class,
      $test_method,
      $data_provider_row,
    );
    $cb = $this->callback;
    await $cb($ev);
  }

  public async function testPassedAsync(
    string $test_method,
    ?(arraykey, Container<mixed>) $data_provider_row,
  ): Awaitable<void> {
    $ev = new HackTest\TestPassedProgressEvent(
      $this->path,
      $this->class,
      $test_method,
      $data_provider_row,
    );
    $cb = $this->callback;
    await $cb($ev);
  }

  public function getErrors(): vec<HackTest\ErrorProgressEvent> {
    return $this->errors;
  }

  /** Return a Skipped/Errored/Failed as appropriate */
  public async function testFinishedWithExceptionAsync(
    string $test_method,
    ?(arraykey, Container<mixed>) $data_provider_row,
    \Throwable $ex,
  ): Awaitable<void> {
    if ($ex is HackTest\SkippedTestException) {
      $ev = new HackTest\TestSkippedProgressEvent(
        $this->path,
        $this->class,
        $test_method,
        $data_provider_row,
      );
    } else if ($ex is HackTest\ExpectationFailedException) {
      $ev = new HackTest\TestFailedProgressEvent(
        $this->path,
        $this->class,
        $test_method,
        $data_provider_row,
        $ex,
      );
    } else {
      $ev = new HackTest\TestErroredProgressEvent(
        $this->path,
        $this->class,
        $test_method,
        $data_provider_row,
        $ex,
      );
    }
    if ($ev is HackTest\ErrorProgressEvent) {
      $this->errors[] = $ev;
    }
    $cb = $this->callback;
    await $cb($ev);
  }
}
