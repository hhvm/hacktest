/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

/* HHAST_IGNORE_ALL[DontAwaitInALoop] */

namespace Facebook\HackTest\_Private;

use namespace HH\Lib\{IO, Str, Vec};
use namespace Facebook\HackTest;
use type Facebook\HackTest\TestResult;

final class ConciseCLIOutput extends CLIOutputHandler {

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

    if (!$e is HackTest\TestFinishedProgressEvent) {
      return;
    }

    switch ($e->getResult()) {
      case TestResult::PASSED:
        await $handle->writeAsync('.');
        break;
      case TestResult::SKIPPED:
        await $handle->writeAsync('S');
        break;
      case TestResult::FAILED:
        await $handle->writeAsync('F');
        break;
      case TestResult::ERROR:
        await $handle->writeAsync('E');
        break;
    }
  }

  private async function writeFailureDetailsAsync(
    <<__AcceptDisposable>> IO\WriteHandle $handle,
  ): Awaitable<void> {
    $error_id = 0;
    foreach ($this->getErrors() as $event) {
      $ex = $event->getException();
      $error_id++;

      $header = $this->getMessageHeaderForErrorDetails($error_id, $event);

      $message = $ex->getMessage();
      if ($event is HackTest\TestSkippedProgressEvent) {
        $message = 'Skipped: '.$ex->getMessage();
      } else if ($event is HackTest\FileProgressEvent) {
        $file = $event->getPath();

        $context = $this->getPrettyContext($ex, $file) ??
          $ex->getTraceAsString()
          |> Str\split($$, '#')
          |> Vec\filter($$, $line ==> Str\contains($line, $file))
          |> Vec\map($$, $line ==> Str\strip_prefix($line, '  '))
          |> Str\join($$, "\n");

        if ($context !== '') {
          $message .= "\n\n".$context;
        }
      }
      await $handle->writeAsync($header.$message);
    }
  }
}
