[![Build Status](https://travis-ci.com/hhvm/hacktest.svg?token=zPvriessjph1qhCX5PxF&branch=master)](https://travis-ci.com/hhvm/hacktest)

# HackTest

HackTest is a pure Hack alternative to PHPUnit. In order to use this framework, you must migrate assert calls to use the [expect](https://github.com/hhvm/fbexpect) API.

## Usage

```
bin/hacktest [OPTIONS] PATH [PATH ...]
```

## Examples

### "I want to test all files in a directory"
```
$ bin/hacktest tests/clean/exit/
HackTest 1.0 by Wilson Lin and contributors.

...

Summary: 3 test(s), 3 passed, 0 failed, 0 skipped, 0 error(s).
```

### "I want to run all tests in a specific file"

```
$ bin/hacktest tests/dirty/DirtyAsyncTest.php
HackTest 1.0 by Wilson Lin and contributors.

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

## Installation

This project uses function autoloading, so requires that your projects use
[hhvm-autoload](https://github.com/hhvm/hhvm-autoload) instead of Composer's
built-in autoloading; if you are not already using hhvm-autoload, you will need
to add an
[hh_autoload.json](https://github.com/hhvm/hhvm-autoload#configuration-hh_autoloadjson)
to your project first.

```
$ composer require hhvm/hacktest
```

## Principles
- Test files must end in 'Test.php' or 'Test.hh'
- Test classes must extend `HackTestCase`
- Class names must match base filenames
- Only test methods and data providers can be public
- Test methods must begin with 'test'

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

The HackTest framework is MIT-licensed.
