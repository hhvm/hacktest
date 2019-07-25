#!/bin/sh
set -ex
hhvm --version
php --version

hh_client

bin/hacktest tests/clean/
if !(hhvm --version | grep -q -- -dev); then
  vendor/bin/hhast-lint
fi
