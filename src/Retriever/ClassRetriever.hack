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
use namespace HH\Lib\{C, Dict, Keyset, Str, Vec};

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
    private keyset<string> $caseInsensitiveClassnames,
  ) {
  }

  public static function forFile(string $path): ClassRetriever {
    return C\onlyx(self::forFiles(keyset[$path]));
  }

  public static function forFiles(
    keyset<string> $paths,
  ): vec<ClassRetriever> {
    if (\ini_get('hhvm.repo.authoritative')) {
      return Vec\map(
        $paths,
        $path ==>
          \Facebook\AutoloadMap\Generated\map()['class']
          |> Dict\filter($$, $class_path ==> $class_path === $path)
          |> Keyset\keys($$)
          |> new self($path, $$),
      );
    }

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
        return new self(
          $path,
          Keyset\map($file_facts['types'], $type ==> $type['name']),
        );
      },
    );
  }

  public function getTestClassName(): ?classname<HackTest> {
    $test_classes = $this->caseInsensitiveClassnames
      |> Vec\filter(
        $$,
        $name ==> \is_subclass_of($name, HackTest::class, true),
      );

    if (C\count($test_classes) !== 1) {
      throw new InvalidTestClassException(
        Str\format(
          'There must be exactly one test class in %s',
          $this->filename,
        ),
      );
    }

    $rc = new \ReflectionClass(C\onlyx($test_classes));
    if ($rc->isAbstract()) {
      return null;
    }
    $name = $rc->getName();  // fixes capitalization

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
        Str\format('%s does not extend %s', $name, HackTest::class),
      );
    }

    return $classname;
  }

  private function convertToClassname(string $name): ?classname<HackTest> {
    try {
      return TypeAssert\classname_of(HackTest::class, $name);
    } catch (TypeAssert\IncorrectTypeException $_) {
      return null;
    }
  }
}
