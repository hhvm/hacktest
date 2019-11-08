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
use namespace HH\Lib\Str;

final class MethodRetrieverTest extends HackTest {

  public function testValidTestMethods(): void {
    $path = 'tests/clean/hsl/dict';
    $file_retriever = new FileRetriever($path);
    foreach ($file_retriever->getTestFiles() as $file) {
      $classname = ClassRetriever::forFile($file)->getTestClassName()
        as nonnull;
      $test_methods = (new $classname())->getTestMethods();
      foreach ($test_methods as $method) {
        expect(Str\starts_with($method->getName(), 'test'))->toBeTrue();
        $type = Str\replace($method->getReturnTypeText(), 'HH\\', '');
        expect($type === 'void' || $type === 'Awaitable<void>')->toBeTrue();
        expect($method->isPublic())->toBeTrue();
      }
    }
  }
}
