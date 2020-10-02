#!/usr/bin/env sh

# Import locales on backend
php bin/console locale:import

# Install NPM dependencies
cd frontend
npm ci

# Import locales on frontend
npm run import-locales

# Build frontend assets
npm run build

eval "$@"
