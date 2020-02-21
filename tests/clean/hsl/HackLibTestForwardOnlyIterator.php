<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use namespace HH\Lib\{C, Vec};

/**
 * Iterator that implements the same behavior as generators when
 * Hack.Lang.AutoprimeGenerators is false
 */
final class HackLibTestForwardOnlyIterator<Tk as arraykey, Tv>
implements \HH\Rx\Iterator<Tv>, \HH\Rx\KeyedIterator<Tk, Tv> {
  private bool $used = false;
  private int $keyIdx = 0;
  private vec<Tk> $keys;

  <<__Rx>>
  public function __construct(private dict<Tk, Tv> $data) {
    $this->keys = Vec\keys($data);
  }

  <<__Rx, __MaybeMutable>>
  public function current(): Tv  {
    return $this->data[$this->keys[$this->keyIdx]];
  }

  <<__Rx, __MaybeMutable>>
  public function key(): Tk {
    return $this->keys[$this->keyIdx];
  }

  <<__Rx, __Mutable>>
  public function rewind(): void {
    if ($this->used) {
      $this->next();
      $this->used = false;
    }
  }

  <<__Rx, __MaybeMutable>>
  public function valid(): bool {
    return C\contains_key($this->keys, $this->keyIdx);
  }

  <<__Rx, __Mutable>>
  public function next(): void {
    $this->used = true;
    $this->keyIdx++;
  }
}
