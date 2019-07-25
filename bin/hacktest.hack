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
  $root = \dirname(__DIR__);
  $found_autoloader = false;
  while (true) {
    try {
      require_once($root.'/vendor/autoload.hack');
      $found_autoloader = true;
      \Facebook\AutoloadMap\initialize();
      break;
    } catch (\Error $_) {
    }
    if ($root === '/') {
      break;
    }
    $root = \dirname($root);
  }

  if (!$found_autoloader) {
    \fprintf(\STDERR, "Failed to find autoloader.\n");
    exit(1);
  }

  $exit_code = await HackTestCLI::runAsync();
  exit($exit_code);
}
