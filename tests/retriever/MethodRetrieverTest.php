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
use type Facebook\HackTest\{FileRetriever, ClassRetriever, MethodRetriever};
use HH\Lib\Str;

final class MethodRetrieverTest extends PHPUnit_Framework_TestCase {

  public function testValidTestMethods(): void {
    $path = '../tuple';
    $file_retriever = new FileRetriever($path);
    foreach ($file_retriever->getTestFiles() as $file) {
      $class = $file->getClass(new ClassRetriever($file)->getTestClassName());
      $test_methods = new MethodRetriever($class)->getTestMethods();
      foreach ($test_methods as $method) {
        expect(Str\starts_with($method->getName(), 'test'))->toBeTrue();
        expect($method->isPublic())->toBeTrue();
      }
    }
  }
}
