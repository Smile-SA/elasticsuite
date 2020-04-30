#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

# Enforce checking out through https instead of SSH.
composer config --global github-protocols https
git config --global url."https://github.com/PMET-public".insteadOf "git@github.com:PMET-public"

# Get back where Magento is installed
cd ..
cd magento

echo "==> Adding Venia Sample Data"
cp "$TRAVIS_BUILD_DIR/Resources/travis/deployVeniaSampleData.sh" .
#wget https://raw.githubusercontent.com/magento/pwa-studio/v5.0.1/packages/venia-concept/deployVeniaSampleData.sh
#sed -e "s?git@github.com\:?https\:\/\/github.com\/?g" --in-place deployVeniaSampleData.sh

bash deployVeniaSampleData.sh --yes

echo "==> Process setup:upgrade and reindex"
php bin/magento setup:upgrade --keep-generated --quiet
php bin/magento index:reindex
