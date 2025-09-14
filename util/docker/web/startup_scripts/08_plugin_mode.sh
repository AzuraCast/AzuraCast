#!/usr/bin/env bash

bool() {
    case "$1" in
    Y* | y* | true | TRUE | 1) return 0 ;;
    esac
    return 1
}

APPLICATION_ENV="${APPLICATION_ENV:-production}"

cd /var/azuracast/www

if [ "$APPLICATION_ENV" = "production" ]; then
    if bool "$COMPOSER_PLUGIN_MODE"; then
        # Find number of folders in the "./plugins" directory.
        PLUGIN_DIRS=$(find ./plugins -mindepth 1 -maxdepth 1 -type d | wc -l)

        # Only run `composer update` if nonzero.
        if [ $PLUGIN_DIRS -gt 0 ]; then
            gosu azuracast composer update --no-dev --optimize-autoloader
        else
            echo "Plugin mode is enabled, but no plugins were detected. Skipping plugin initialization..."
        fi
    fi
fi
