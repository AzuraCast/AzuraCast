#!/bin/bash

set -e

if [ $TEST = "unit-tests" ]; then

  echo "Running unit-tests"
  export DISPLAY=:99.0
  sh -e /etc/init.d/xvfb start
  sleep 1
  grunt karma:coverage
  CODECLIMATE_REPO_TOKEN=64800c476bad6ab9d10d0ff0901ae2c211457852f28c5f960ae5165c1fdfec73 codeclimate-test-reporter < coverage/*/lcov.info

elif [[ $TEST = "browser-tests" ]]; then

  echo "Running browser-tests"
  grunt karma:saucelabs

fi
