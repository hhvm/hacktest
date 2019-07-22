/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest\_Private;

/**
 * Codegen Hack files to store data.
 *
 * Using Hack files so that we don't need real files or the static content
 * cache, which is unavailable in repo-authoritative mode.
 */
final class CacheFile {
  public function __construct(private string $path) {
  }

  private function getFunctionName(): string {
    return 'hacktest_cache_'.\bin2hex(\sodium_crypto_generichash($this->path));
  }

  public function store(mixed $x): void {
    \file_put_contents(
      $this->path,
      'function '.
      $this->getFunctionName().
      "(): mixed {\n".
      'return '.
      \var_export($x, true).
      ";\n}\n",
    );
  }

  public function fetch(): mixed {
    require_once($this->path);
    $fun = $this->getFunctionName();
    return /* HH_FIXME[4009] */ $fun();
  }
}
