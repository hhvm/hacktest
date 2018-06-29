#!/bin/sh
set -ex
hhvm --version

composer install

hh_client

bin/hacktest tests/clean/
hhvm vendor/bin/hhast-lint

echo > .hhconfig
hh_server --check $(pwd)
