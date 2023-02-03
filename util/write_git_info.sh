#!/usr/bin/env bash

# Ensure we're in the same directory as this script.
cd "$( dirname "${BASH_SOURCE[0]}" )" || exit

cd ..

COMMIT_LONG=$(git log --pretty=%H -n1 HEAD)
COMMIT_DATE=$(git log -n1 --pretty=%ci HEAD)

LAST_TAG_HASH=$(git rev-list --tags --max-count=1)
LAST_TAG=$(git describe --tags $LAST_TAG_HASH)

BRANCH=$(git rev-parse --abbrev-ref HEAD main | head -n 1)

printf "COMMIT_LONG=\"%s\"\n" "$COMMIT_LONG" > .gitinfo
printf "COMMIT_DATE=\"%s\"\n" "$COMMIT_DATE" >> .gitinfo
printf "LAST_TAG=\"%s\"\n" "$LAST_TAG" >> .gitinfo
printf "BRANCH=\"%s\"\n" "$BRANCH" >> .gitinfo
