#!/usr/bin/env sh

# Install Composer dependencies
composer install --no-interaction --ignore-platform-req=ext-maxminddb

# Import locales on backend
bin/console locale:generate

# Install NPM dependencies
cd frontend
npm ci

# Import locales on frontend
npm run generate-locales

eval "$@"
