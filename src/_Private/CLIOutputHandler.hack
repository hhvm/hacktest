/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest\_Private;

use namespace HH\Lib\{C, Dict, IO, Math, Str, Vec};
use namespace Facebook\HackTest;

abstract class CLIOutputHandler {
  <<__LateInit>> private dict<HackTest\TestResult, int> $resultCounts;
  <<__LateInit>> private vec<HackTest\ErrorProgressEvent> $errors;

  const int CONTEXT_LINES = 3;

  public function __construct(private \Facebook\CLILib\ITerminal $terminal) {
  }

  final public async function writeProgressAsync(
    <<__AcceptDisposable>> IO\WriteHandle $handle,
    \Facebook\HackTest\ProgressEvent $e,
  ): Awaitable<void> {
    if ($e is HackTest\TestRunStartedProgressEvent) {
      $this->reset();
      return;
    }

    $this->logEvent($e);

    await $this->writeProgressImplAsync($handle, $e);
  }

  abstract protected function writeProgressImplAsync(
    <<__AcceptDisposable>> IO\WriteHandle $handle,
    \Facebook\HackTest\ProgressEvent $e,
  ): Awaitable<void>;

  final private function reset(): void {
    $this->resultCounts = Dict\fill_keys(HackTest\TestResult::getValues(), 0);
    $this->errors = vec[];
  }

  final private function logEvent(HackTest\ProgressEvent $e): void {
    if ($e is HackTest\TestFinishedProgressEvent) {
      $this->resultCounts[$e->getResult()]++;
    }

    if ($e is HackTest\ErrorProgressEvent) {
      $this->errors[] = $e;
      if (!$e is HackTest\TestFinishedProgressEvent) {
        $this->resultCounts[HackTest\TestResult::ERROR]++;
      }
    }
  }

  final protected function getErrors(): vec<HackTest\ErrorProgressEvent> {
    return $this->errors;
  }

  final protected function getMessageHeaderForErrorDetails(
    int $message_num,
    HackTest\ErrorProgressEvent $ev,
  ): string {
    if (!$ev is HackTest\TestFinishedWithExceptionProgressEvent) {
      if ($ev is HackTest\ClassProgressEvent) {
        return Str\format("\n\n%d) %s\n", $message_num, $ev->getClassname());
      }
      if ($ev is HackTest\FileProgressEvent) {
        return Str\format("\n\n%d) %s\n", $message_num, $ev->getPath());
      }
      return "\n\n".$message_num.")\n";
    }

    $row = $ev->getDataProviderRow();
    if ($row is nonnull) {
      return Str\format(
        "\n\n%d) %s::%s with data set #%s\n",
        $message_num,
        $ev->getClassname(),
        $ev->getTestMethod(),
        (string)$row[0],
      );
    } else {
      return Str\format(
        "\n\n%d) %s::%s\n",
        $message_num,
        $ev->getClassname(),
        $ev->getTestMethod(),
      );
    }
  }

  final public function getResultCounts(): dict<HackTest\TestResult, int> {
    return $this->resultCounts;
  }

  final protected async function writeSummaryAsync(
    <<__AcceptDisposable>> IO\WriteHandle $handle,
  ): Awaitable<void> {
    $result_counts = $this->getResultCounts();
    $num_tests = Math\sum($result_counts);

    await $handle->writeAsync(Str\format(
      "\n\nSummary: %d test(s), %d passed, %d failed, %d skipped, %d error(s).\n",
      $num_tests,
      $result_counts[HackTest\TestResult::PASSED] ?? 0,
      $result_counts[HackTest\TestResult::FAILED] ?? 0,
      $result_counts[HackTest\TestResult::SKIPPED] ?? 0,
      $result_counts[HackTest\TestResult::ERROR] ?? 0,
    ));
  }

  final protected function getPrettyContext(
    \Throwable $ex,
    string $file,
  ): ?string {
    if (!\file_exists($file)) {
      // Possibly running in repo-authoritative mode
      return null;
    }

    $frame = $ex->getTrace()
      |> Vec\filter(
        $$,
        $row ==>
          (($row as KeyedContainer<_, _>)['file'] ?? null) as ?string === $file,
      )
      |> C\last($$);

    if (!$frame is KeyedContainer<_, _>) {
      return null;
    }
    $colors = $this->terminal->supportsColors();
    $c_light = $colors ? "\e[2m" : '';
    $c_bold = $colors ? "\e[1m" : '';
    $c_red = $colors ? "\e[31m" : '';
    $c_reset = $colors ? "\e[0m" : '';

    $line = $frame['line'] as int;
    $line_number_width = Str\length((string)$line) + 2;

    $first_line = Math\maxva(1, $line - self::CONTEXT_LINES);
    $all_lines = \file_get_contents($file)
      |> Str\split($$, "\n");

    $context_lines = Vec\slice(
      $all_lines,
      $first_line - 1,
      ($line - $first_line),
    )
      |> Vec\map_with_key(
        $$,
        ($n, $content) ==> Str\format(
          '%s| %s%s%s',
          Str\pad_left((string)($n + $first_line), $line_number_width, ' '),
          $c_light,
          $content,
          $c_reset,
        ),
      );

    $blame_line = $all_lines[$line - 1];
    $fun = $frame['function'] as string;
    $fun_offset = Str\search($blame_line, $fun.'(');
    if ($fun_offset is null && Str\contains($fun, '\\')) {
      $fun = Str\split($fun, '\\') |> C\lastx($$);
      $fun_offset = Str\search($blame_line, $fun.'(');
    }
    if (
      $fun_offset is null && $frame['function'] === 'HH\\invariant_violation'
    ) {
      $fun = 'invariant';
      $fun_offset = Str\search($blame_line, 'invariant(');
    }

    if ($fun_offset is null) {
      $context_lines[] = Str\format(
        '%s%s>%s %s%s',
        Str\pad_left((string)$line, $line_number_width),
        $c_red,
        $c_reset.$c_bold,
        $blame_line,
        $c_reset,
      );
    } else {
      $context_lines[] = Str\format(
        '%s%s>%s %s%s%s%s%s%s',
        Str\pad_left((string)$line, $line_number_width),
        $c_red,
        $c_reset.$c_bold,
        Str\slice($blame_line, 0, $fun_offset),
        $c_red,
        $fun,
        $c_reset.$c_bold,
        Str\slice($blame_line, $fun_offset + Str\length($fun)),
        $c_reset,
      );

      $context_lines[] = Str\format(
        '%s%s%s%s',
        Str\repeat(' ', $line_number_width + $fun_offset + 2),
        $c_red,
        Str\repeat('^', Str\length($fun)),
        $c_reset,
      );
    }
    return $file.':'.$line."\n".Str\join($context_lines, "\n");
  }
}
