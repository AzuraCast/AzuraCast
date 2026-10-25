#!/bin/bash
set -e
set -x

export PMTILES_VERSION=1.31.2
export BASEMAPS_ASSETS_COMMIT=028c18f713baecad011301ff7a69acc39bcc2ae7

cd /tmp

ARCHITECTURE=x86_64
if [[ "$(uname -m)" = "aarch64" ]]; then
    ARCHITECTURE=arm64
fi

wget --quiet -O pmtiles.tar.gz "https://github.com/protomaps/go-pmtiles/releases/download/v${PMTILES_VERSION}/go-pmtiles_${PMTILES_VERSION}_Linux_${ARCHITECTURE}.tar.gz"
tar -xzf pmtiles.tar.gz pmtiles

BUILD_DATE=$(date -u -d "yesterday" +'%Y%m%d')

mkdir -p /var/azuracast/maps
./pmtiles extract "https://build.protomaps.com/${BUILD_DATE}.pmtiles" \
    /var/azuracast/maps/basemap.pmtiles --maxzoom=6

# Fonts & sprites for MapLibre text/icon rendering
wget --quiet -O basemaps-assets.tar.gz "https://github.com/protomaps/basemaps-assets/archive/${BASEMAPS_ASSETS_COMMIT}.tar.gz"
mkdir -p /var/azuracast/maps/assets
tar -xzf basemaps-assets.tar.gz --strip-components=1 -C /var/azuracast/maps/assets \
    "basemaps-assets-${BASEMAPS_ASSETS_COMMIT}/fonts" \
    "basemaps-assets-${BASEMAPS_ASSETS_COMMIT}/sprites"

rm -f pmtiles pmtiles.tar.gz basemaps-assets.tar.gz
chown -R azuracast:azuracast /var/azuracast/maps
