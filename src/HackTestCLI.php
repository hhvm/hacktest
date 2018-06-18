<?hh // strict
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
use HH\Lib\C;

/** The main `hacktest` CLI */
final class HackTestCLI extends CLIWithRequiredArguments {

  private int $verbosity = 0;

  <<__Override>>
  public static function getHelpTextForRequiredArguments(): vec<string> {
    return vec['SOURCE_PATH'];
  }

  <<__Override>>
  protected function getSupportedOptions(): vec<CLIOptions\CLIOption> {
    return vec[
      CLIOptions\flag(
        () ==> {
          $this->verbosity++;
        },
        "Increase output verbosity",
        '--verbose',
        '-v',
      ),
    ];
  }

  <<__Override>>
  public async function mainAsync(): Awaitable<int> {
    $this->getStdout()
      ->write("HackTest 1.0 by Wilson Lin and contributors.\n\n");
    $output = '';
    $num_error = 0;
    $num_errors = 0;
    $num_tests = 0;

    foreach ($this->getArguments() as $path) {
      $file_retriever = new FileRetriever($path);
      foreach ($file_retriever->getTestFiles() as $file) {
        $result = await HackTestRunner::runTestFileAsync($file);
        $class_name = C\firstx($file->getClassNames());
        $num_tests += C\count($result);
        foreach ($result as $method => $res) {
          if ($res instanceof \Exception) {
            $this->getStdout()->write('F');
            $num_errors++;
            if ($this->verbosity) {
              $output .= "\n\nClass: ".$class_name."\nMethod: ".$method."\n";
              $output .= ++$num_error.") ".$res->__toString();
            }
          } else {
            $this->getStdout()->write('.');
          }
        }
      }
    }
    $this->writeVerbose($output);
    $this->getStdout()
      ->write(
        "\n\nSummary: ".
        $num_tests.
        " test(s), ".
        ($num_tests - $num_errors).
        " passed, ".
        $num_errors.
        " failed.\n",
      );
    return 0;
  }

  public function writeVerbose(string $output): void {
    if (!$this->verbosity) {
      return;
    }
    $this->getStdout()->write($output);
  }
}
