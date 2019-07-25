#!/bin/sh
set -ex
apt update -y
DEBIAN_FRONTEND=noninteractive apt install -y php-cli zip unzip
hhvm --version
php --version

(
  cd $(mktemp -d)
  curl https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
)
composer install

hh_client

bin/hacktest tests/clean/
if !(hhvm --version | grep -q -- -dev); then
  vendor/bin/hhast-lint
fi

# also run tests in repo-authoritative mode
REPO_DIR=$(mktemp -d)

hhvm --hphp --target hhbc -l 3 \
  --module bin \
  --module src \
  --module tests \
  --module vendor \
  --ffile bin/hacktest \
  --output-dir $REPO_DIR

hhvm --no-config \
  -d hhvm.repo.authoritative=true \
  -d hhvm.repo.central.path=$REPO_DIR/hhvm.hhbc \
  bin/hacktest tests/clean/
