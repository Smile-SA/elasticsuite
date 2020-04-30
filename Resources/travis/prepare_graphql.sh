#!/usr/bin/env bash
set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR
echo '==> Preparing GraphQL lightweight test suite.'
npm install -g graphqurl
