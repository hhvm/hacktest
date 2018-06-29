# Contributing to HackTest
We want to make contributing to this project as easy and transparent as
possible.

## Our Development Process

This project is developed in the 'master' branch on GitHub. Changes should be submitted as pull requests.

Usually, versions will be tagged directly from master; if changes are needed to
an old release, a branch will be cut from the previous release in that series - e.g.
v1.2.3 might be tagged from a `1.2.x` branch.

## Pull Requests
We actively welcome your pull requests.
1. Fork the repo and create your branch from `master`.
2. If you've added code that should be tested, add tests
3. Ensure the test suite passes on `tests/clean/` and the example still works
4. If you haven't already, complete the Contributor License Agreement ("CLA").

## Contributor License Agreement ("CLA")
In order to accept your pull request, we need you to submit a CLA. You only need to do this once to work on any of Facebook's open source projects.

Complete your CLA here: <https://code.facebook.com/cla>

## Issues
We use GitHub issues to track public bugs. Please ensure your description is clear and has sufficient instructions to be able to reproduce the issue.

Facebook has a [bounty program](https://www.facebook.com/whitehat/) for the safe disclosure of security bugs. In those cases, please go through the process outlined on that page and do not file a public issue.

## Core Components

- `Facebook\HackTest\HackTestCLI`: Kicks off the test runner and writes the results to `STDOUT`. Currently, only the verbose flag is supported. We hope to support different options as well as CLI modes in the future.
- `Facebook\HackTest\HackTestRunner`: Creates and executes test cases given `PATH` arguments,  returning the results to the CLI.
- `Facebook\HackTest\HackTestCase`: An individual test case that uses `ReflectionClass` to retrieve and run all the test methods in a file.
- `Facebook\HackTest/Retriever`: Classes that retrieve and validate test files and class names.

## Coding Style
* 2 spaces for indentation rather than tabs
* 80 character line length
* Please be consistent with the existing code style

`hackfmt` or in-IDE code formatting (in 3.27+) and `hhast-lint` will
enforce most of the rules.

## License
By contributing to HackTest, you agree that your contributions will be licensed under its MIT license.
