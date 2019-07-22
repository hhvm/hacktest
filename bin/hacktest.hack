#!/usr/bin/env hhvm
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

<<__EntryPoint>>
async function hack_test_main_async(): Awaitable<noreturn> {
  $autoloaders = vec[
    // vendor/facebook/hacktest/bin/hacktest
    __DIR__.'/../../../autoload.hack',
    // bin/hacktest
    __DIR__.'/../vendor/autoload.hack',
    // same, but relative to CWD for repo-auth mode
    '../../../autoload.hack',
  ];
  $parts = \explode('/', __DIR__);
  for ($i = \count($parts) - 2; $i >= 0; --$i) {
    $autoloaders[] = \array_slice($parts, 0, $i)
      |> \implode('/', $$)
      |> $$.'/vendor/autoload.hack';
  }
  $found_autoloader = false;
  foreach ($autoloaders as $autoloader) {
    try {
      require_once($autoloader);
      $found_autoloader = true;
      \Facebook\AutoloadMap\initialize();
      break;
    } catch (\Error $_) {
    }
  }

  if (!$found_autoloader) {
    \fprintf(\STDERR, "Failed to find autoloader.\n");
    exit(1);
  }

  $exit_code = await HackTestCLI::runAsync();
  exit($exit_code);
}
