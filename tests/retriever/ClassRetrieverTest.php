<?hh // strict
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use function Facebook\FBExpect\expect;
use type Facebook\HackTest\{FileRetriever, ClassRetriever};
use HH\Lib\{C, Str};

final class ClassRetrieverTest extends PHPUnit_Framework_TestCase {

  public function testClassMatchFileName(): void {
    $path = '../tuple';
    $file_retriever = new FileRetriever($path);
    foreach ($file_retriever->getTestFiles() as $file) {
      $cr = new ClassRetriever($file);
      $classname = $cr->getTestClassName()
        |> Str\split($$, '\\')
        |> C\lastx($$);
      $filename = $file->getFilename()
        |> Str\split($$, '/')
        |> C\lastx($$)
        |> \explode('.', $$)[0];

      expect($classname)->toBeSame($filename);
    }
  }
}