#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

echo "==> Running a lightweight GraphQL test scenario."

echo "==> Testing Search"
gq \
     http://localhost/graphql \
     -l \
     --variablesFile="$TRAVIS_BUILD_DIR/Resources/tests/graphql/search/variables.json" \
     --queryFile="$TRAVIS_BUILD_DIR/Resources/tests/graphql/search/query.gql"

echo "==> Testing Category"
gq \
     http://localhost/graphql \
     -l \
     --variablesFile="$TRAVIS_BUILD_DIR/Resources/tests/graphql/category/variables.json" \
     --queryFile="$TRAVIS_BUILD_DIR/Resources/tests/graphql/category/query.gql"

echo "==> Testing Product Detail"
gq \
     http://localhost/graphql \
     -l \
     --variablesFile="$TRAVIS_BUILD_DIR/Resources/tests/graphql/productdetail/variables.json" \
     --queryFile="$TRAVIS_BUILD_DIR/Resources/tests/graphql/productdetail/query.gql"
