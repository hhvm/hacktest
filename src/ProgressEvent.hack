/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

abstract class ProgressEvent {
  <<__ReturnDisposable>>
  final public function onScopeExit(
    (function(this): Awaitable<void>) $callback,
  ): \IAsyncDisposable {
    return new _Private\OnScopeExitAsync(async () ==> await $callback($this));
  }
}

final class TestRunStartedProgressEvent extends ProgressEvent {}
final class TestRunFinishedProgressEvent extends ProgressEvent {}

abstract class FileProgressEvent extends ProgressEvent {
  public function __construct(private string $path) {
  }

  final public function getPath(): string {
    return $this->path;
  }
}

interface ErrorProgressEvent {
  require extends ProgressEvent;
  public function getException(): \Throwable;
}

final class FileErrorProgressEvent
  extends FileProgressEvent
  implements ErrorProgressEvent {
  public function __construct(string $path, private \Throwable $ex) {
    parent::__construct($path);
  }

  public function getException(): \Throwable {
    return $this->ex;
  }
}

abstract class ClassProgressEvent extends FileProgressEvent {
  public function __construct(
    string $path,
    private classname<HackTest> $class,
  ) {
    parent::__construct($path);
  }

  final public function getClassname(): classname<HackTest> {
    return $this->class;
  }
}

final class StartingTestClassEvent extends ClassProgressEvent {}
final class FinishedTestClassEvent extends ClassProgressEvent {}

abstract class TestProgressEvent extends ClassProgressEvent {
  public function __construct(
    string $path,
    classname<HackTest> $class,
    private string $testMethod,
  ) {
    parent::__construct($path, $class);
  }

  public function getTestMethod(): string {
    return $this->testMethod;
  }
}

final class InvokingDataProvidersProgressEvent extends TestProgressEvent {}

abstract class TestInstanceProgressEvent extends TestProgressEvent {
  public function __construct(
    string $path,
    classname<HackTest> $class,
    string $testMethod,
    private ?(arraykey, Container<mixed>) $dataProviderRow,
  ) {
    parent::__construct($path, $class, $testMethod);
  }

  final public function getDataProviderRow(): ?(arraykey, Container<mixed>) {
    return $this->dataProviderRow;
  }
}

final class TestStartingProgressEvent extends TestInstanceProgressEvent {}

abstract class TestFinishedProgressEvent extends TestInstanceProgressEvent {
  abstract public function getResult(): TestResult;
}

final class TestPassedProgressEvent extends TestFinishedProgressEvent {
  <<__Override>>
  public function getResult(): TestResult {
    return TestResult::PASSED;
  }
}

final class TestSkippedProgressEvent extends TestFinishedProgressEvent {
  <<__Override>>
  public function getResult(): TestResult {
    return TestResult::SKIPPED;
  }
}

abstract class TestFinishedWithExceptionProgressEvent
  extends TestFinishedProgressEvent
  implements ErrorProgressEvent {
  public function __construct(
    string $path,
    classname<HackTest> $class,
    string $testMethod,
    ?(arraykey, Container<mixed>) $dataProviderRow,
    private \Throwable $ex,
  ) {
    parent::__construct($path, $class, $testMethod, $dataProviderRow);
  }

  public function getException(): \Throwable {
    return $this->ex;
  }
}

final class TestFailedProgressEvent
  extends TestFinishedWithExceptionProgressEvent {
  <<__Override>>
  public function getResult(): TestResult {
    return TestResult::FAILED;
  }
}

final class TestErroredProgressEvent
  extends TestFinishedWithExceptionProgressEvent {
  <<__Override>>
  public function getResult(): TestResult {
    return TestResult::ERROR;
  }
}
