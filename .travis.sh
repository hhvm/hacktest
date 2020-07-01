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
#if !(hhvm --version | grep -q -- -dev); then
#  vendor/bin/hhast-lint
#fi

# also run tests in repo-authoritative mode
REPO_DIR=$(mktemp -d)

# Exclude vendor/bin/ to work around issue in HHVM 4.62
# https://github.com/facebook/hhvm/issues/8719
hhvm --hphp --target hhbc -l 3 \
  --module bin \
  --module src \
  --module tests \
  --module vendor \
  --ffile bin/hacktest \
  --exclude-dir vendor/bin \
  --output-dir $REPO_DIR

# make sure we don't have any of the source files handy
cd $(mktemp -d)

hhvm --no-config \
  -d hhvm.repo.authoritative=true \
  -d hhvm.repo.central.path=$REPO_DIR/hhvm.hhbc \
  bin/hacktest tests/clean/
