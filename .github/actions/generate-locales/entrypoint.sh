#!/usr/bin/env sh

# Install Composer dependencies
composer install --no-interaction

# Import locales on backend
php bin/console locale:generate

# Install NPM dependencies
cd frontend
npm ci

# Import locales on frontend
npm run generate-locales

eval "$@"
