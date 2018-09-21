[![Build Status](https://travis-ci.com/hhvm/hacktest.svg?token=zPvriessjph1qhCX5PxF&branch=master)](https://travis-ci.com/hhvm/hacktest)

# HackTest

HackTest is a pure Hack alternative to PHPUnit. In order to use this framework, you must migrate assert calls to use the [expect](https://github.com/hhvm/fbexpect) API.

## Installation

```
hhvm /path/to/composer.phar require --dev hhvm/hacktest facebook/fbexpect
```

## Usage

To run tests:

```
vendor/bin/hacktest [OPTIONS] tests/
```

To migrate PHPUnit tests to HackTest:

```
git clone https://github.com/hhvm/hhast.git
cd hhast
bin/hhast-migrate --phpunit-to-hacktest /path/to/myproject/tests
```

Tests are methods in classes, where:
- the class name matches the file name
- the class name ends with 'Test'
- the method is public
- the method name begins with 'test'

Test methods can be async, and will automatically be awaited.

Additionally, classes can implement several special methods:

- `public static function beforeFirstTestAsync(): Awaitable<void>`
- `public static function afterLastTestAsync(): Awaitable<void>`
- `public function beforeEachTestAsync(): Awaitable<void>`
- `public function afterEachTestAsync(): Awaitable<void>`

Finally, for data-driven tests, the `<<DataProvider>>` attribute can be used:

```Hack
public function provideFoos(): vec<(string, int)> {
  return vec[
    tuple('foo', 123),
    tuple('bar', 456),
  ];
}

<<DataProvider('provideFoos')>>
public function testFoos(string $a, int $b): void {
  ....
}
```

## Examples

### "I want to test all files in a directory"
```
$ bin/hacktest tests/clean/exit/

...

Summary: 3 test(s), 3 passed, 0 failed, 0 skipped, 0 error(s).
```

### "I want to run all tests in a specific file"

```
$ bin/hacktest tests/dirty/DirtyAsyncTest.php

FFF

1) DirtyAsyncTest::testWithNonNullableTypesAsync
Failed asserting that Array &0 (
    0 => 1
    1 => 'foo'
) is not identical to Array &0 (
    0 => 1
    1 => 'foo'
).

/fakepath/hacktest/tests/dirty/DirtyAsyncTest.php(22): Facebook\FBExpect\ExpectObj->toNotBeSame()
/fakepath/hacktest/src/Framework/HackTestCase.php(43): DirtyAsyncTest->testWithNonNullableTypesAsync()

2)...

Summary: 3 test(s), 0 passed, 3 failed, 0 skipped, 0 error(s).
```

For an example in verbose mode, see [example.txt](example.txt)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

The HackTest framework is MIT-licensed.
