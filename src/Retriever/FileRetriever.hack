/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use namespace HH\Lib\Str;

final class FileRetriever {
  private string $path;

  public function __construct(private string $rawPath) {
    $this->path = \realpath($rawPath);
  }

  public function getTestFiles(): keyset<string> {
    if (\ini_get('hhvm.repo.authoritative')) {
      return /* HH_FIXME[4110] reified generics */
       (new _Private\CacheFile($this->getCacheFile()))->fetch();
    }
    return $this->getTestFilesFromDisk();
  }

  private function getCacheFile(): string {
    if (Str\ends_with($this->rawPath, '/')) {
      return $this->rawPath.'hacktest-files-cache.hack';
    }
    return $this->rawPath.'.hacktest-files-cache.hack';
  }

  public function storeTestFilesListForRepoAuth(): void {
    // Full paths are more useful, but not safe to deal with in repos
    $this->path = $this->rawPath;
    $files = $this->getTestFilesFromDisk();
    (new _Private\CacheFile($this->getCacheFile()))->store($files);
  }

  private function getTestFilesFromDisk(): keyset<string> {
    $files = keyset[];
    if (!\is_dir($this->path)) {
      $file = $this->path;
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
      new \RecursiveDirectoryIterator($this->path),
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
