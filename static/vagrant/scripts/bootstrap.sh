#!/usr/bin/env bash

PHP_V=$1

echo "..............................."
echo "StartUP - GO"
/etc/init.d/nginx restart
/etc/init.d/php${PHP_V}-fpm restart
/etc/init.d/mysql restart
ntpdate pool.ntp.org
echo "StartUP - END"
echo "..............................."