#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

# Prepare a full Magento2 project with proper version.
cd ..

# If no stability configured, do nothing and keep installing the given Magento2 version.
if [[ -z "${MAGENTO_STABILITY}" ]]; then
  STABILITY="--stability=stable"
  VERSION="$MAGENTO_VERSION"
else
  # Else, switch explicit version to closest one, and set composer to prefer given stability.
  # Eg. if we want to test 2.3.0 which is in beta, composer will not be able to install it with magento/project-community-edition=2.3.0
  # Proper syntax for installing would be : magento/project-community-edition=2.3.* --stability=beta
  # That's the purpose of the 2 following lines.
  STABILITY="--stability=$MAGENTO_STABILITY"
  VERSION="${MAGENTO_VERSION%.*}.*"
fi

echo "==> Copying Magento2 repository credentials here."
cp "$TRAVIS_BUILD_DIR/auth.json" .

echo "==> Installing Magento 2 $MAGENTO_EDITION (Version $VERSION) ..."
echo "composer create-project --repository-url=https://repo.magento.com magento/project-$MAGENTO_EDITION-edition=$VERSION $STABILITY magento"
composer create-project --repository-url=https://repo.magento.com magento/project-$MAGENTO_EDITION-edition=$VERSION $STABILITY magento --quiet

cd "magento"

# Require the extension to make it usable (autoloading)
echo "==> Requiring smile/elasticsuite from the $TRAVIS_BRANCH-dev branch"
composer require --dev "smile/elasticsuite:$TRAVIS_BRANCH-dev" --quiet

# Arbitray copy the pulled build (current travis build) in place of the required one.
echo "==> Copying the current build to the Magento 2 installation."
cp -R ../elasticsuite/* vendor/smile/elasticsuite/

echo "==> Installing Magento 2"
mysql -uroot -e 'CREATE DATABASE magento2;'
php bin/magento setup:install -q --admin-user="admin" --admin-password="smile1234" --admin-email="admin@example.com" --admin-firstname="Admin" --admin-lastname="Smile" --db-name="magento2"

echo "==> Process upgrade and try to compile..."
php bin/magento setup:upgrade -q
php bin/magento cache:flush
php bin/magento setup:di:compile
