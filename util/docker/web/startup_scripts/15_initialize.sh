#!/bin/bash

set -ex

exec sudo -E -u azuracast azuracast_cli azuracast:setup:initialize
