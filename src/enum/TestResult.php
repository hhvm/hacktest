<?hh // strict

namespace Facebook\HackTest;

enum TestResult: int {
  PASSED = 0;
  FAILED = 1;
  ERROR = 2;
  SKIPPED = 3;
}
