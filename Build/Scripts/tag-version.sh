#!/bin/bash
set -e

if [ -z "$1" ]; then
  echo "Usage: my-git-tag <version>"
  echo "Example: my-git-tag 0.0.26"
  exit 1
fi

VERSION="$1"

if ! [[ "$VERSION" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
  echo "Version must be in format x.y.z (e.g. 0.0.26)"
  exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
  echo "Working tree is not clean. Commit or stash changes first."
  exit 1
fi

echo "Setting version to $VERSION..."

jq ".extra.\"typo3/cms\".version = \"$VERSION\"" composer.json > tmp.json && mv tmp.json composer.json

sed -i "s/'version' => '[^']*'/'version' => '$VERSION'/" ext_emconf.php

git add composer.json ext_emconf.php
git commit -m "Set version to $VERSION"
git tag "$VERSION"

echo "Done! Tag $VERSION created."
