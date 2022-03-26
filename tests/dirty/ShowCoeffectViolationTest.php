<?hh // strict
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

use namespace Facebook\HackTest;
use function Facebook\FBExpect\expect;

final class ShowCoeffectViolationTest extends HackTest\HackTest {
  /*
    Typing[4323] A where type constraint is violated here [1]
    -> This is the method with where type constraints [2]
    -> Expected a function that requires the capability set {AccessGlobals, IO, ImplicitPolicyLocal, RxLocal, System, WriteProperty} [3]
    -> But got a function that requires nothing [4]
    
    tests/SomeTest.hack:7:23
          5 |   public function testShowCoeffectViolation(): void {
          6 |     self::markTestSkipped('This test is pointless');
    [1]   7 |     expect(() ==> 3)->toThrow(\Exception::class);
          8 |   }
          9 | }
    
    vendor/facebook/fbexpect/src/ExpectObj.hack:569:19
        567 |    * the awaitable will be awaited.
        568 |    *
    [2] 569 |   public function toThrow<TException as \Throwable, TRet>(
        570 |     classname<TException> $exception_class,
        571 |     ?string $expected_exception_message = null,
        572 |     ?string $msg = null,
        573 |     mixed ...$args
    [3] 574 |   ): TException where T = (function(): TRet) {
        575 |     $msg = \vsprintf($msg ?? '', $args);
        576 |     $exception = $this->tryCallReturnException($exception_class);
    
    .:0:0
    [4]   0 | No source found
    
    1 error found.
    */
  public function testShowCoeffectViolation(): void {
    self::markTestSkipped('This test is pointless');
    /* HH_FIXME[4323] The error shown above. */
    expect(() ==> 3)->toThrow(\Exception::class);
  }

  public function testWithoutCoeffectViolation(): void {
    self::markAsSkipped('This test is pointless');
    expect(() ==> 3)->toThrow(\Exception::class);
  }
}