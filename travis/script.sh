#!/bin/bash
mkdir tests/files
cp -R $SAMPLEDATA_PATH/files tests/files

if [[ $TRAVIS_PHP_VERSION != "7.0" ]]; then
  vendor/bin/phpunit --coverage-text --coverage-clover ./build/logs/clover.xml
else
  vendor/bin/phpunit
fi

# tips hat to Pádraic Brady, Dave Marshall, Wouter, Graham Campbell for this
