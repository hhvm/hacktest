/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest\_Private;

use namespace HH\Lib\{Dict, Math, Str};
use namespace HH\Lib\Experimental\IO;
use namespace Facebook\HackTest;

abstract class CLIOutputHandler {
	<<__LateInit>> private dict<HackTest\TestResult, int> $resultCounts;
	<<__LateInit>> private vec<HackTest\ErrorProgressEvent> $errors;

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
}
