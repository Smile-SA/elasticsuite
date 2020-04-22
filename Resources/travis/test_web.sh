#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

echo "==> Testing homepage..."
curl_status=`curl --silent --connect-timeout 8 --output /dev/null -LI http://localhost/ -LI -w "%{http_code}\n"`
echo ${curl_status}
if [[ ${curl_status} -ge 400 ]];
then
  exit 2;
fi;

echo "==> Testing catalogsearch..."
curl_status=`curl --silent --connect-timeout 8 --output /dev/null -LI http://localhost/catalogsearch/result/?q=top -LI -w "%{http_code}\n"`
echo ${curl_status}
if [[ ${curl_status} -ge 400 ]];
then
  exit 2;
fi;

echo "==> Testing autocomplete..."
curl_status=`curl --silent --connect-timeout 8 --output /dev/null -LI http://localhost/search/ajax/suggest/?q=top -LI -w "%{http_code}\n"`
echo ${curl_status}
if [[ ${curl_status} -ge 400 ]];
then
  exit 2;
fi;

sleep 5
