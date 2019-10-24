/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest\_Private;

<<__Sealed(WrappedResult::class, WrappedException::class)>>
interface ResultOrException<T> {
}

final class WrappedResult<T> implements ResultOrException<T> {
  public function __construct(private T $result) {
  }

  public function getResult(): T {
    return $this->result;
  }
}

final class WrappedException<T> implements ResultOrException<T> {
  public function __construct(private \Throwable $ex) {
  }

  public function getException(): \Throwable {
    return $this->ex;
  }
}
