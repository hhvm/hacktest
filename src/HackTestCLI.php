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
use type Facebook\DefinitionFinder\{FileParser};
use HackTestCase;

/** The main `hacktest` CLI */
final class HackTestCLI extends CLIWithRequiredArguments {
  <<__Override>>
  public static function getHelpTextForRequiredArguments(): vec<string> {
    return vec['SOURCE_PATH'];
  }


  <<__Override>>
  protected function getSupportedOptions(): vec<CLIOptions\CLIOption> {
    return vec[];
  }

  <<__Override>>
  public async function mainAsync(): Awaitable<int> {
    $this->run($this->getArguments());
    return 0;
  }

  public function run(vec<string> $argv): int {

    // TODO: put in a test runner class
    $file_retriever = new FileRetriever($argv[0]);
    foreach ($file_retriever->getAllFiles() as $file) {
      $cr = new ClassRetriever($file);
      $class_name = $cr->getTestClassName();
      $class = $file->getClass($class_name);
      \printf("%s\n", $class_name);

      $mr = new MethodRetriever($class);
      $method_names = $mr->getTestMethodNames();
      foreach ($method_names as $name) {
        //\printf("%s ", $name);
      }
      //\printf("\n");
      $htc = new HackTestCase($class_name, $method_names);
      $htc->run();
    }
    return 0;
  }

}
