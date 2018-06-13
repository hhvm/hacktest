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

use namespace Facebook\TypeAssert;
use HH\Lib\{C, Str, Vec};
use type Facebook\DefinitionFinder\FileParser;
use type HackTestCase;
use type InvalidTestClassException;
use function Facebook\FBExpect\expect;

class ClassRetriever {

  public function __construct(private FileParser $fp) {
  }

  public function getTestClassName(): classname<HackTestCase> {
    $test_classes =
      Vec\filter($this->fp->getClassNames(), $name ==> Str\ends_with($name, 'Test'));

    if (C\count($test_classes) !== 1) {
      throw new InvalidTestClassException("Only one test class allowed per file");
    }

    $name = $test_classes[0];
    $classname = $name
      |> Str\split($$, '\\')
      |> C\lastx($$);
    $filename = $this->fp->getFilename()
      |> Str\split($$, '/')
      |> C\lastx($$)
      |> Str\split($$, '.')
      |> C\firstx($$);

    if ($classname !== $filename) {
      throw new InvalidTestClassException('Class name must match filename');
    }
    if (!Str\ends_with($classname, 'Test')) {
      throw new InvalidTestClassException("Class name must end with 'Test'");
    }
    $name = TypeAssert\classname_of(HackTestCase::class, $name);

    return $name;
  }
}
