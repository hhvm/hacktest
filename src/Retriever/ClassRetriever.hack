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
use type Facebook\HackTest\_Private\{
  ResultOrException,
  WrappedException,
  WrappedResult,
};

final class ClassRetriever {
  const type TFacts = shape(
    'types' => varray<shape(
      'name' => string,
      'baseTypes' => varray<string>,
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
    $res = C\onlyx(self::forFiles(keyset[$path]));
    if ($res is WrappedResult<_>) {
      return $res->getResult();
    }
    throw ($res as WrappedException<_>)->getException();
  }

  public static function forFiles(
    keyset<string> $paths,
  ): dict<string, ResultOrException<ClassRetriever>> {
    if (\ini_get('hhvm.repo.authoritative')) {
      return Dict\map(
        $paths,
        $path ==> \Facebook\AutoloadMap\Generated\map()['class']
          |> Dict\filter($$, $class_path ==> $class_path === $path)
          |> Keyset\keys($$)
          |> new WrappedResult(new self($path, $$)),
      );
    }

    $all_facts = \HH\facts_parse(
      /* root = */ '/',
      varray($paths),
      /* force_hh = */ false,
      /* multithreaded = */ true,
    );
    return Dict\map(
      $paths,
      $path ==> {
        try {
          $file_facts = TypeAssert\matches_type_structure(
            type_structure(self::class, 'TFacts'),
            $all_facts[$path],
          );
          return new WrappedResult(new self(
            $path,
            Keyset\map($file_facts['types'], $type ==> $type['name']),
          ));
        } catch (TypeAssert\IncorrectTypeException $e) {
          return new WrappedException(
            new InvalidTestFileException('Could not parse file.'),
          );
        }
      },
    );
  }

  public function getTestClassName(): ?classname<HackTest> {
    $test_classes = $this->caseInsensitiveClassnames
      |> Vec\filter(
        $$,
        $name ==> \is_subclass_of($name, HackTest::class, true),
      );

    $count = C\count($test_classes);
    if ($count !== 1) {
      $all_classes = '';
      if (!C\is_empty($this->caseInsensitiveClassnames)) {
        $all_classes = ':';
        foreach ($this->caseInsensitiveClassnames as $cn) {
          $rc = new \ReflectionClass($cn);
          $all_classes .= "\n - ".$rc->getName();
          if ($rc->isSubclassOf(HackTest::class)) {
            $all_classes .= ' (is a test class)';
          } else {
            $all_classes .= ' (is not a subclass of '.HackTest::class.')';
          }
        }
      }
      throw new InvalidTestClassException(
        Str\format(
          'There must be exactly one test class in %s; found %d%s',
          $this->filename,
          $count,
          $all_classes,
        ),
      );
    }

    $rc = new \ReflectionClass(C\onlyx($test_classes));
    if ($rc->isAbstract()) {
      return null;
    }
    $name = $rc->getName(); // fixes capitalization

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
