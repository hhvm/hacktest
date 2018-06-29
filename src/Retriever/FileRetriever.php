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
use namespace HH\Lib\Str;

final class FileRetriever {

  public function __construct(private string $path = '.') {
  }

  public function getTestFiles(): vec<FileParser> {
    $files = vec[];
    if (!\is_dir($this->path)) {
      $file = $this->path;
      if (!\is_file($file)) {
        throw new InvalidTestFileException(
          Str\format('File (%s) not found', $file),
        );
      }
      if ($this->isTestFile($file)) {
        return vec[FileParser::fromFile($file)];
      }
      throw new InvalidTestFileException(
        Str\format(
          '%s is not a valid test file (ending in Test.php or Test.hh)',
          $file,
        ),
      );
    }
    $rii = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($this->path),
    );

    foreach ($rii as $file) {
      $filename = $file->getPathname();
      if (!$file->isDir() && $this->isTestFile($filename)) {
        $files[] = FileParser::fromFile($filename);
      }
    }

    return $files;
  }

  private function isTestFile(string $filename): bool {
    return \preg_match('/Test(\.php|\.hh)$/', $filename) === 1;
  }

}
