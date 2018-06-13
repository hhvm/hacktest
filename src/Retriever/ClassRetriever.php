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
use function Facebook\FBExpect\expect;
use HH\Lib\{C, Str, Vec};

class ClassRetriever {

  public function __construct(private FileParser $fp) {
  }

  public function getTestClassName(): string {
    $name = '';

    $test_classes =
      Vec\filter($this->fp->getClassNames(), $name ==> Str\ends_with($name, 'Test'));

    // TODO: replace with InvalidTestClassException
    invariant(
      C\count($test_classes) == 1,
      "Only one test class per file allowed.",
    );

    $name = $test_classes[0];
    // TODO: expect extends new base class
    $classname = $name
      |> Str\split($$, '\\')
      |> C\lastx($$);
    $filename = $this->fp->getFilename()
      |> Str\split($$, '/')
      |> C\lastx($$)
      |> \explode('.', $$)[0];

    // TODO: replace with InvalidTestClassException
    invariant(
      $classname === $filename,
      'Class name is not the same as file name.',
    );
    invariant(
      Str\ends_with($classname, 'Test'),
      "Class name must end with 'Test'.",
    );

    return $name;
  }
}
