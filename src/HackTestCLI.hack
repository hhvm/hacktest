/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use type Facebook\CLILib\CLIWithRequiredArguments;
use namespace Facebook\CLILib\CLIOptions;
use namespace HH\Lib\{C, Math, Str};

/** The main `hacktest` CLI */
final class HackTestCLI extends CLIWithRequiredArguments {
  private bool $verbose = false;
  private ?string $classFilter = null;
  private ?HackTestRunner::TMethodFilter $methodFilter = null;

  <<__Override>>
  public static function getHelpTextForRequiredArguments(): vec<string> {
    return vec['SOURCE_PATH'];
  }

  <<__Override>>
  protected function getSupportedOptions(): vec<CLIOptions\CLIOption> {
    return vec[
      CLIOptions\with_required_string(
        $f ==> {
          $this->classFilter = $f;
        },
        'Filter test class names with the specified glob pattern',
        '--filter-classes',
      ),
      CLIOptions\with_required_string(
        $f ==> {
          $mf = $this->methodFilter;
          $impl = (mixed $_class, \ReflectionMethod $method) ==>
            \fnmatch($f, $method->getName());
          $this->methodFilter = $mf
            ? (
                (classname<HackTest> $c, \ReflectionMethod $m) ==>
                  $mf($c, $m) && $impl($c, $m)
              )
            : $impl;
        },
        'Filter test method names with the specified glob pattern',
        '--filter-methods',
      ),
      CLIOptions\with_required_string(
        $groups ==> {
          $groups = Str\split($groups, ',') |> keyset($$);
          $mf = $this->methodFilter;
          $impl = (mixed $_class, \ReflectionMethod $method) ==> {
            $attr = $method->getAttributeClass(TestGroup::class);
            if ($attr === null) {
              return false;
            }
            return C\any($groups, $group ==> $attr->contains($group));
          };
          $this->methodFilter = $mf
            ? (
                (classname<HackTest> $c, \ReflectionMethod $m) ==>
                  $mf($c, $m) && $impl($c, $m)
              )
            : $impl;
        },
        'Only run tests with a specified <<TestGroup>> (comma-separated)',
        '--filter-groups',
        '-g',
      ),
      CLIOptions\flag(
        () ==> {
          $this->verbose = true;
        },
        'Increase output verbosity',
        '--verbose',
        '-v',
      ),
    ];
  }

  <<__Override>>
  public async function mainAsync(): Awaitable<int> {
    $cf = $this->classFilter;
    $mf = $this->methodFilter;
    $output = $this->verbose
      ? new _Private\VerboseCLIOutput($this->getTerminal())
      : new _Private\ConciseCLIOutput($this->getTerminal());
    $stdout = $this->getStdout();

    await HackTestRunner::runAsync(
      $this->getArguments(),
      shape(
        'classes' => (
          $cf === null ? ($_ ==> true) : ($c ==> \fnmatch($cf, $c))
        ),
        'methods' => ($mf ?? ($_class, $_method) ==> true),
      ),
      async $event ==> await $output->writeProgressAsync($stdout, $event),
    );

    $result_counts = $output->getResultCounts();

    if (Math\sum($result_counts) === 0) {
      await $this->getStderr()->writeAsync("No tests found.\n");
      return ExitCode::ERROR;
    }

    if (($result_counts[TestResult::ERROR] ?? 0) > 0) {
      return ExitCode::ERROR;
    }
    if (($result_counts[TestResult::FAILED] ?? 0) > 0) {
      return ExitCode::FAILURE;
    }
    return ExitCode::SUCCESS;
  }
}
