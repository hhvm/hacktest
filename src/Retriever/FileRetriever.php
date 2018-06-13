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
use HH\Lib\Str;

class FileRetriever {

  public function __construct(private string $dir = '') {
  }

  public function getTestFiles(): vec<FileParser> {
    $vec = vec[];
    // TODO: get files recursively
    $glob = \glob(\realpath($this->dir).'/*');
    foreach ($glob as $filename) {
      if (\preg_match('/Test(.php|.hh)$/', $filename)) {
        $vec[] = FileParser::FromFile($filename);
      }
    }
    // TODO: test individual files

    return $vec;
  }

}
