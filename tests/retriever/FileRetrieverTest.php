<?hh
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

final class FileRetrieverTest extends \PHPUnit_Framework_TestCase {

  public function testValidTestFiles(): void {
    $path = 'tests/tuple';
    $file_retriever = new FileRetriever($path);
    foreach ($file_retriever->getTestFiles() as $file) {
      expect(\preg_match('/Test(.php|.hh)$/', $file->getFilename()) === 1)->toBeTrue();
    }
  }
}
