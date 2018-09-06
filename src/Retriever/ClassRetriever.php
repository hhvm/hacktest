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

final class ClassRetriever {
  const type TFacts = shape(
    'types' => array<shape(
      'name' => string,
      'baseTypes' => array<string>,
      'kindOf' => string,
      ...
    )>,
    ...
  );

  public function __construct(
    private string $filename,
    private self::TFacts $facts,
  ) {
  }

  public static function forFile(string $path): ClassRetriever {
    return C\onlyx(self::forFiles(keyset[$path]));
  }

  public static function forFiles(
    keyset<string> $paths,
  ): vec<ClassRetriever> {
    $all_facts = \HH\facts_parse(
      /* root = */ '/',
      /* HH_FIXME[4007] need a PHP array here for now */
      (array) $paths,
      /* force_hh = */ false,
      /* multithreaded = */ true,
    );
    return Vec\map(
      $paths,
      $path ==> {
        $file_facts = TypeAssert\matches_type_structure(
          type_structure(self::class, 'TFacts'),
          $all_facts[$path],
        );
        return new self($path, $file_facts);
      },
    );
  }

  public function getTestClassName(): classname<HackTestCase> {
    $test_classes = $this->facts['types']
      |> Vec\map($$, $t ==> $t['name'])
      |> Vec\filter(
        $$,
        $name ==> \is_subclass_of($name, HackTestCase::class, true),
      );

    if (C\count($test_classes) !== 1) {
      throw new InvalidTestClassException(
        Str\format(
          'There must be exactly one test class in %s',
          $this->filename,
        ),
      );
    }

    $name = C\onlyx($test_classes);
    $class_name = $name
      |> Str\split($$, '\\')
      |> C\lastx($$);
    $filename = $this->filename
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
