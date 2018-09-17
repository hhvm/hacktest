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

use function Facebook\FBExpect\expect;
use namespace HH\Lib\{C, Str};

final class ClassRetrieverTest extends HackTestCase {

  public function testClassMatchFileName(): void {
    $path = 'tests/clean/hsl/tuple';
    $files = (new FileRetriever($path))->getTestFiles();
    foreach ($files as $file) {
      $cr = ClassRetriever::forFile($file);
      $classname = $cr->getTestClassName() as nonnull
        |> Str\split($$, '\\')
        |> C\lastx($$);
      $filename = $file
        |> Str\split($$, '/')
        |> C\lastx($$)
        |> Str\split($$, '.')
        |> C\firstx($$);

      expect($classname)->toBeSame($filename);
    }
  }
}
