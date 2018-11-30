#!/bin/sh
set -ex
hhvm --version

composer install --ignore-platform-reqs

hh_client

bin/hacktest tests/clean/
if !(hhvm --version | grep -q -- -dev); then
  hhvm vendor/bin/hhast-lint
fi

echo > .hhconfig
hh_server --check $(pwd)
