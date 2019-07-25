/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use namespace HH\Lib\{Keyset, Str};

final class FileRetriever {

  public function __construct(private string $path) {
    $this->path = Str\strip_suffix($this->path, '/');
  }

  public function getTestFiles(): keyset<string> {
    if (\ini_get('hhvm.repo.authoritative')) {
      return \Facebook\AutoloadMap\Generated\map()['class']
        |> Keyset\filter(
          $$,
          $filename ==> (
            $filename === $this->path ||
            Str\starts_with($filename, $this->path.'/')
          ) && $this->isTestFile($filename),
        );
    }

    $path = \realpath($this->path);
    $files = keyset[];
    if (!\is_dir($path)) {
      $file = $path;
      if (!\is_file($file)) {
        throw new InvalidTestFileException(
          Str\format('File (%s) not found', $file),
        );
      }
      if ($this->isTestFile($file)) {
        return keyset[$file];
      }
      throw new InvalidTestFileException(
        Str\format(
          '%s is not a valid test file (ending in Test.php or Test.hh)',
          $file,
        ),
      );
    }
    $rii = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($path),
    );

    foreach ($rii as $file) {
      $filename = $file->getPathname();
      if (!$file->isDir() && $this->isTestFile($filename)) {
        $files[] = $filename;
      }
    }

    return $files;
  }

  private function isTestFile(string $filename): bool {
    return \preg_match('/Test(\.php|\.hh|\.hack|\.hck)$/', $filename) === 1;
  }

}
