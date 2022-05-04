#!/bin/bash
set -e
set -x

# Per-architecture Stereo Tool installs
ARCHITECTURE=cmd
ARM_FULL_BUILD="${ARM_FULL_BUILD:-false}"

if [[ "$(uname -m)" = "aarch64" && ${ARM_FULL_BUILD} == "false" ]]; then
    ARCHITECTURE=pi2
fi

wget -O /usr/bin/stereo_tool "https://www.stereotool.com/download/stereo_tool_${ARCHITECTURE}_64"

chmod +x /usr/bin/stereo_tool

ln -s /usr/bin/stereo_tool /usr/local/bin/stereo_tool
