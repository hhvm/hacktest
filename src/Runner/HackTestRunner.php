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

use type Facebook\DefinitionFinder\FileParser;

class HackTestRunner {

  public static async function runTestFileAsync(
    FileParser $file,
  ): Awaitable<dict<string, mixed>> {
    $class_name = new ClassRetriever($file)->getTestClassName();
    $class = $file->getClass($class_name);
    $methods = new MethodRetriever($class)->getTestMethods();
    $htc = new HackTestCase($class_name, $methods);
    $result = await $htc->runAsync();

    return $result;
  }
}
