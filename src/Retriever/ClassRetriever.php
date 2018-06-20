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
use namespace HH\Lib\{C, Str, Vec};
use type Facebook\DefinitionFinder\FileParser;
use function Facebook\FBExpect\expect;

final class ClassRetriever {

  public function __construct(private FileParser $fp) {
  }

  public function getTestClassName(): classname<HackTestCase> {
    $test_classes = Vec\filter(
      $this->fp->getClassNames(),
      $name ==> \is_subclass_of($name, HackTestCase::class, true),
    );

    if (C\count($test_classes) !== 1) {
      throw new InvalidTestClassException(
        'There must be exactly one test class per file'
      );
    }

    $name = C\onlyx($test_classes);
    $classname = $name
      |> Str\split($$, '\\')
      |> C\lastx($$);
    $filename = $this->fp->getFilename()
      |> Str\split($$, '/')
      |> C\lastx($$)
      |> Str\split($$, '.')
      |> C\firstx($$);

    if ($classname !== $filename) {
      throw new InvalidTestClassException(
        'Class name must match filename'
      );
    }
    if (!Str\ends_with($classname, 'Test')) {
      throw new InvalidTestClassException(
        'Class name must end with \'Test\''
      );
    }
    try {
      $name = TypeAssert\classname_of(HackTestCase::class, $name);
    } catch (TypeAssert\IncorrectTypeException $_) {
      throw new InvalidTestClassException(
        Str\format(
          "%s does not extend %s",
          $name,
          HackTestCase::class
        ),
      );
    }

    return $name;
  }
}
