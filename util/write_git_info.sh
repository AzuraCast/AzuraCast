#!/usr/bin/env bash

# Ensure we're in the same directory as this script.
cd "$( dirname "${BASH_SOURCE[0]}" )" || exit

cd ..

COMMIT_LONG=$(git log --pretty=%H -n1 HEAD)
COMMIT_DATE=$(git log -n1 --pretty=%ci HEAD)

BRANCH=$(git rev-parse --abbrev-ref HEAD | head -n 1)

printf "COMMIT_LONG=\"%s\"\n" "$COMMIT_LONG" > .gitinfo
printf "COMMIT_DATE=\"%s\"\n" "$COMMIT_DATE" >> .gitinfo
printf "BRANCH=\"%s\"\n" "$BRANCH" >> .gitinfo
