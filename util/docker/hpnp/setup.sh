#!/bin/bash
set -e
set -x

curl -fsSL https://bun.sh/install | gosu azuracast bash
ln -s /var/azuracast/.bun/bin/bun /usr/local/bin/bun
ln -s /var/azuracast/.bun/bin/bunx /usr/local/bin/bunx

cd /var/azuracast/www/frontend
gosu azuracast npm ci
gosu azuracast npm run build-hpnp

mv ./hpnp /usr/local/bin/hpnp
chmod a+x /usr/local/bin/hpnp

rm -rf /var/azuracast/www/frontend/node_modules
rm -rf /var/azuracast/.bun
rm -rf /usr/local/bin/bun
rm -rf /usr/local/bin/bunx

cp -rT /bd_build/hpnp/service.full/. /etc/supervisor/full.conf.d/
