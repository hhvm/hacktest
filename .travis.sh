#!/bin/sh
set -ex
hhvm --version

composer install

hh_client

hhvm vendor/bin/phpunit tests/
hhvm vendor/bin/hhast-lint

echo > .hhconfig
hh_server --check $(pwd)
