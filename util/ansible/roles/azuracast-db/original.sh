#!/usr/bin/env bash

# Set up DB.
echo "Setting up database..."

cd $util_base

phpuser doctrine.php orm:schema-tool:create
phpuser cli.php cache:clear