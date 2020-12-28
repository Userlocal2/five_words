#!/usr/bin/env bash
PHP_VERSION_REQUIREMENT="7.4"
PHP_CHECK=`which php${PHP_VERSION_REQUIREMENT}`
if [ ! -e "$PHP_CHECK" ]
then
        echo -e "----> Requirement version of PHP does not exist\n----> Getting default version"
        PHP_CHECK=`which php`
        if [ ! -e "$PHP_CHECK" ]
        then
                echo -e "====> Default vwrsion of PHP dows not exist. The PHP intalled? \nEXIT."
                exit 1
        fi
fi
PHP=$PHP_CHECK
echo "----> Using: $PHP"
echo "----> Version: `${PHP} -v | grep cli  | awk '{print $2}' | cut -f1 -d '-'`"


CONNECTION=$1
if [ -z "$1" ]
  then
    CONNECTION='default'
fi


${PHP} bin/cake.php migrations migrate --connection=${CONNECTION}
wait

${PHP} bin/cake.php migrations migrate -p Currency --connection=${CONNECTION}
wait

${PHP} bin/cake.php migrations seed --connection=${CONNECTION}
wait

${PHP} bin/cake.php migrations seed -p Currency --connection=${CONNECTION}
wait

echo all done
