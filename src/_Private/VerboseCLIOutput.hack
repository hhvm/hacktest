/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 j
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

/* HHAST_IGNORE_ALL[DontAwaitInALoop] */

namespace Facebook\HackTest\_Private;

use namespace HH\Lib\{IO, Str};

use namespace Facebook\HackTest;
use type Facebook\HackTest\TestResult;

final class VerboseCLIOutput extends CLIOutputHandler {
  <<__Override>>
  protected async function writeProgressImplAsync(
    <<__AcceptDisposable>> IO\WriteHandle $handle,
    HackTest\ProgressEvent $e,
  ): Awaitable<void> {
    if ($e is HackTest\TestRunFinishedProgressEvent) {
      await $this->writeFailureDetailsAsync($handle);
      await $this->writeSummaryAsync($handle);
      return;
    }

    if ($e is HackTest\TestFinishedProgressEvent) {
      switch ($e->getResult()) {
        case TestResult::PASSED:
          await $handle->writeAsync("PASS\n");
          break;
        case TestResult::SKIPPED:
          await $handle->writeAsync("SKIP\n");
          break;
        case TestResult::FAILED:
          await $handle->writeAsync("FAIL\n");
          break;
        case TestResult::ERROR:
          await $handle->writeAsync("ERROR\n");
          break;
      }
    }

    if ($e is HackTest\InvokingDataProvidersProgressEvent) {
      $message = 'calling data providers...';
    } else if ($e is HackTest\TestStartingProgressEvent) {
      $message = 'starting...';
    } else if ($e is HackTest\TestFinishedProgressEvent) {
      $message = '...complete.';
    } else {
      return;
    }

    if ($e is HackTest\TestProgressEvent) {
      $scope = '  ::'.$e->getTestMethod();
      if ($e is HackTest\TestInstanceProgressEvent) {
        $dp = $e->getDataProviderRow();
        if ($dp is nonnull) {
          $scope .= '['.(string)$dp[0].']';
        }
      }
    } else if ($e is HackTest\ClassProgressEvent) {
      $scope = $e->getClassname();
    } else {
      $scope = $e->getPath();
    }

    await $handle->writeAsync($scope.'> '.$message."\n");
  }

  private async function writeFailureDetailsAsync(
    <<__AcceptDisposable>> IO\WriteHandle $handle,
  ): Awaitable<void> {
    $error_id = 0;
    foreach ($this->getErrors() as $event) {
      $ex = $event->getException();
      $error_id++;
      $header = $this->getMessageHeaderForErrorDetails($error_id, $event);

      if ($event is HackTest\TestSkippedProgressEvent) {
        await $handle->writeAsync($header.'Skipped: '.$ex->getMessage());
        return;
      }

      $it = $ex;
      $message = '';
      while ($it) {
        $message .= Str\format(
          "%s\n\n@ %s(%d)\n%s",
          $it->getMessage(),
          $it->getFile(),
          $it->getLine(),
          $it->getTraceAsString(),
        );
        $it = $it->getPrevious();
        if ($it !== null) {
          $message .= "\n\nPrevious exception:\n\n";
        }
      }
      if ($event is HackTest\FileProgressEvent) {
        $file = $event->getPath();
        $context = $this->getPrettyContext($ex, $file);
        if ($context is nonnull) {
          $message .= "\n\n".$context;
        }
      }
      await $handle->writeAsync($header.$message);
    }
  }
}
