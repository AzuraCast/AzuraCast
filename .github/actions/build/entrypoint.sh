#!/usr/bin/env sh

# Install Composer dependencies if not already installed.
composer install --no-interaction --ignore-platform-reqs

# Import locales on backend
bin/console locale:import

# Install NPM dependencies
cd frontend
npm ci

# Import locales on frontend
npm run import-locales

# Build frontend assets
npm run build

eval "$@"
