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
        Str\format(
          'There must be exactly one test class in %s',
          $this->fp->getFilename(),
        ),
      );
    }

    $name = C\onlyx($test_classes);
    $class_name = $name
      |> Str\split($$, '\\')
      |> C\lastx($$);
    $filename = $this->fp->getFilename()
      |> Str\split($$, '/')
      |> C\lastx($$)
      |> Str\split($$, '.')
      |> C\firstx($$);

    if ($class_name !== $filename) {
      throw new InvalidTestClassException(
        Str\format(
          'Class name (%s) must match filename (%s)',
          $class_name,
          $filename,
        ),
      );
    }
    if (!Str\ends_with($class_name, 'Test')) {
      throw new InvalidTestClassException(
        Str\format('Class name (%s) must end with Test', $class_name),
      );
    }
    $classname = $this->convertToClassname($name);
    if ($classname === null) {
      throw new InvalidTestClassException(
        Str\format('%s does not extend %s', $name, HackTestCase::class),
      );
    }

    return $classname;
  }

  private function convertToClassname(string $name): ?classname<HackTestCase> {
    try {
      return TypeAssert\classname_of(HackTestCase::class, $name);
    } catch (TypeAssert\IncorrectTypeException $_) {
      return null;
    }
  }
}
