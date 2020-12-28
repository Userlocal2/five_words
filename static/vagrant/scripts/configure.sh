#!/usr/bin/env bash

PHP_V=$1

echo "...................................................."
echo "...................................................."
echo "...................................> Start Configure"
echo "...................................................."

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq ; echo "apt-get updated"

echo ".............................> Prepering Environment"
sed -i "s/PermitRootLogin without-password/PermitRootLogin yes/" /etc/ssh/sshd_config
sed -i "s/PasswordAuthentication no/PasswordAuthentication yes/" /etc/ssh/sshd_config
/etc/init.d/ssh restart
mkdir /root/.ssh
cat /tmp/source-file/key/id_rsa.pub >> /root/.ssh/authorized_keys
useradd -g root -G sudo -s /bin/bash -d /home/developer -m  developer
echo "developer:payb"|chpasswd


echo ".....> Install..PHP..${PHP_V}"
apt-get -qq remove apache2 -y
mkdir /var/log/php
apt-get install -y apt-transport-https lsb-release ca-certificates > /dev/null
wget -q -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
apt-get update -qq ; echo "apt-get updated"
apt-get install -y php${PHP_V} php${PHP_V}-{fpm,intl,mbstring,curl,xml,soap,mysql,sqlite,pgsql,redis,bcmath,gmp,gd,imagick,opcache,zip,x} > /dev/null
php -v

echo "...> Configure XDebug"
echo "; xdebug
zend_extension=xdebug.so
xdebug.remote_enable=1
xdebug.remote_connect_back=1" > /etc/php/${PHP_V}/mods-available/xdebug.ini

echo "...> Install PHP Timezone DB (TDB)"
apt-get -y install php${PHP_V}-dev php-pear > /dev/null
pecl channel-update pecl.php.net
pecl install timezonedb > /dev/null

echo "...> Configure PHP"
echo "error_log = php_errors.log" >> /etc/php/${PHP_V}/fpm/php.ini
echo "extension=timezonedb.so" >> /etc/php/${PHP_V}/fpm/php.ini
echo "error_log = php_errors.log" >> /etc/php/${PHP_V}/cli/php.ini
echo "extension=timezonedb.so" >> /etc/php/${PHP_V}/cli/php.ini


echo "....> Install..NGINX.."
apt-get install -y nginx > /dev/null
nginx -v


echo "....> Install MySQL.."
echo 'deb http://repo.mysql.com/apt/debian/ stretch mysql-apt-config' >> /etc/apt/sources.list.d/mysql.list
echo 'deb http://repo.mysql.com/apt/debian/ stretch mysql-5.7' >> /etc/apt/sources.list.d/mysql.list
echo 'deb http://repo.mysql.com/apt/debian/ stretch mysql-tools' >> /etc/apt/sources.list.d/mysql.list
echo 'deb-src http://repo.mysql.com/apt/debian/ stretch mysql-5.7' >> /etc/apt/sources.list.d/mysql.list

echo "....> Add  MySQL key"
wget -q -O /tmp/RPM-GPG-KEY-mysql https://repo.mysql.com/RPM-GPG-KEY-mysql
apt-key add /tmp/RPM-GPG-KEY-mysql

apt-get update -qq ; echo "apt-get updated"
mysqlpassword="root"
debconf-set-selections <<< "mysql-server mysql-server/root_password password $mysqlpassword"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $mysqlpassword"
apt-get install -y mysql-community-server > /dev/null
mysql -V

echo "....> Configure..MySQL.."
echo 'bind-address = 0.0.0.0' >> /etc/mysql/mysql.conf.d/mysqld.cnf
echo 'skip-character-set-client-handshake' >> /etc/mysql/mysql.conf.d/mysqld.cnf
echo 'collation_server=utf8_general_ci' >> /etc/mysql/mysql.conf.d/mysqld.cnf
echo 'character_set_server=utf8' >> /etc/mysql/mysql.conf.d/mysqld.cnf
/etc/init.d/mysql restart

if [[ -f /var/lib/mysql/science/db.opt ]]
then
	echo "XXXXXXXXX: Database already exist"
else
    echo "XX - Execute query"
    echo "XX - Set rights to local"
    mysql -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD(''); FLUSH PRIVILEGES;"
    echo "XX - Set rights to %"
    mysql -e "CREATE USER 'root'@'%' IDENTIFIED BY '$mysqlpassword'; FLUSH PRIVILEGES;"
    echo "XX - Set grants"
    mysql -uroot -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;"
    mysql -uroot -e "GRANT ALL PRIVILEGES ON *.* TO 'vagrant'@'%' IDENTIFIED BY 'vagrant' WITH GRANT OPTION;"
    mysql -uroot -e "GRANT ALL PRIVILEGES ON *.* TO 'vagrant'@'localhost' IDENTIFIED BY 'vagrant' WITH GRANT OPTION;"
    mysql -uroot -e "CREATE DATABASE science;"
    mysql -uroot -e "CREATE DATABASE science_test;"
fi

/etc/init.d/mysql restart

echo "...> Configure NGINX"
rm /etc/nginx/sites-enabled/default
cp /tmp/source-file/nginx-config  /etc/nginx/sites-enabled/science.conf


echo "...> Configure SSL"
SSL_C=UA
SSL_ST=Dnepr
SSL_L=Dnepr
SSL_O=Paybtqtd
EMAIL=admin@science.local

cd /etc/nginx || { echo "Failure cd /etc/nginx"; exit 1; }
mkdir certs
cd certs || { echo "Failure cd certs"; exit 1; }
sudo openssl genrsa -out "vagrantbox.key" 2048 &>/dev/null
sudo openssl req -new -key "vagrantbox.key" -out "vagrantbox.csr" -subj "/C=${SSL_C}/ST=${SSL_ST}/L=${SSL_L}/O=${SSL_O}/OU=${SSL_O}/CN=science/emailAddress=${EMAIL}" &>/dev/null
sudo openssl x509 -req -days 365 -in "vagrantbox.csr" -signkey "vagrantbox.key" -out "vagrantbox.crt" &>/dev/null


echo "...> Restart NGINX & PHP & MySQL"
/etc/init.d/nginx restart
/etc/init.d/php${PHP_V}-fpm restart
/etc/init.d/mysql restart

echo "....> Install programs"
apt-get install -y mc htop zip unzip mtr git > /dev/null
mv /tmp/source-file/mc.config /root/.config
cp -R /root/.config /home/vagrant/
chown vagrant:vagrant -R /home/vagrant/.config
cp -R /root/.config /home/developer/
chown developer -R /home/developer/.config

echo "....> Install composer"
cd /tmp/source-file || { echo "Failure cd /tmp/source-file"; exit 1; }
wget -q https://getcomposer.org/composer.phar
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer


echo "....> Cleaning apt-install"
apt-get -qq autoremove
apt-get -qq clean

cd /var/www/cake || { echo "Failure cd /var/www/cake"; exit 1; }
yes | composer install
bash init.sh
