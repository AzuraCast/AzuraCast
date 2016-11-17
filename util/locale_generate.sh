#!/usr/bin/env bash

find /var/azuracast/www -type f \( -name '*.php' -or -name '*.phtml' \) -print > /var/azuracast/list
xgettext --files-from=/var/azuracast/list --language=PHP -o /var/azuracast/www/app/locale/default.pot