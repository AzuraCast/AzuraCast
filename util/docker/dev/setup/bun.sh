#!/bin/bash
set -e
set -x

curl -fsSL https://bun.sh/install | gosu azuracast bash

ln -s /var/azuracast/.bun/bin/bun /usr/local/bin/bun
ln -s /var/azuracast/.bun/bin/bunx /usr/local/bin/bunx
