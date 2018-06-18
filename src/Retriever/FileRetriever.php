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

  public function __construct(private string $path = '.') {
  }

  public function getTestFiles(): vec<FileParser> {
    $files = vec[];
    if (!\is_dir($this->path)) {
      $file = $this->path;
      if ($this->isTestFile($file)) {
        $files[] = FileParser::FromFile($file);
        return $files;
      }
      throw new InvalidTestFileException(
        "Test file does not end in 'Test.php' or 'Test.hh'",
      );
    }
    $rii = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->path),
    );
    $filenames = vec[];
    foreach ($rii as $filename) {
      if (!$filename->isDir())
        $filenames[] = $filename->getPathname();
    }
    foreach ($filenames as $filename) {
      if ($this->isTestFile($filename)) {
        $files[] = FileParser::FromFile($filename);
      }
    }

    return $files;
  }

  private function isTestFile(string $filename): bool {
    return \preg_match('/Test(.php|.hh)$/', $filename) === 1;
  }

}
