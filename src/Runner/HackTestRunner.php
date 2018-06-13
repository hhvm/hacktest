<?hh  // strict
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use type HackTestCase;
use Facebook\HackTest\{FileRetriever, ClassRetriever, MethodRetriever};

class HackTestRunner {

  public static function run(vec<string> $paths): void {
    $file_retriever = new FileRetriever($paths[0]);
    foreach ($file_retriever->getTestFiles() as $file) {
      $class_name = new ClassRetriever($file)->getTestClassName();
      $class = $file->getClass($class_name);
      $method_names = new MethodRetriever($class)->getTestMethodNames();
      $htc = new HackTestCase($class_name, $method_names);
      $htc->run();
    }
  }

}
