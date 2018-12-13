#!/usr/bin/env bash

PHP_V=$1

echo "...................................................."
echo "...................................................."
echo "...................................> Start Configure"
echo "...................................................."
echo ".............................> Prepering Environment"
echo ".....> Install..PHP..${PHP_V}"
sed -i "s/PermitRootLogin without-password/PermitRootLogin yes/" /etc/ssh/sshd_config
sed -i "s/PasswordAuthentication no/PasswordAuthentication yes/" /etc/ssh/sshd_config
/etc/init.d/ssh restart
mkdir /root/.ssh
cat /tmp/source-file/key/id_rsa.pub >> /root/.ssh/authorized_keys
useradd -g root -G sudo -s /bin/bash -d /home/developer -m  developer
echo "developer:payb"|chpasswd
mkdir /var/log/php
apt-get update
apt-get install -qy apt-transport-https lsb-release ca-certificates puppet python-apt linux-headers-$(uname -r) build-essential dkms libicu-dev openssl libssl-ocaml
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
apt-get update
apt-get -y install php${PHP_V} php${PHP_V}-fpm php${PHP_V}-dev php${PHP_V}-intl php${PHP_V}-mbstring php${PHP_V}-xml php${PHP_V}-mysql php${PHP_V}-curl php${PHP_V}-sqlite php-sqlite3 php${PHP_V}-bcmath php${PHP_V}-gd php${PHP_V}-imagick php${PHP_V}-opcache php${PHP_V}-zip php${PHP_V}-x

pecl install timezonedb
echo "error_log = php_errors.log" >> /etc/php/${PHP_V}/fpm/php.ini
echo "extension=timezonedb.so" >> /etc/php/${PHP_V}/fpm/php.ini
echo "error_log = php_errors.log" >> /etc/php/${PHP_V}/cli/php.ini
echo "extension=timezonedb.so" >> /etc/php/${PHP_V}/cli/php.ini
echo "....> Install..NGINX..1-6"
apt-get remove apache2 -y
apt-get install nginx -y



echo "....> Install MySQL.."
echo 'deb http://repo.mysql.com/apt/debian/ jessie mysql-apt-config' >> /etc/apt/sources.list.d/mysql.list
echo 'deb http://repo.mysql.com/apt/debian/ jessie mysql-5.6' >> /etc/apt/sources.list.d/mysql.list
echo 'deb http://repo.mysql.com/apt/debian/ jessie mysql-tools' >> /etc/apt/sources.list.d/mysql.list
echo 'deb-src http://repo.mysql.com/apt/debian/ jessie mysql-5.6' >> /etc/apt/sources.list.d/mysql.list
apt-get update
export DEBIAN_FRONTEND=noninteractive

mysqlpassword="root"
debconf-set-selections <<< "mysql-server mysql-server/root_password password $mysqlpassword"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $mysqlpassword"
apt-get install -q -y  --force-yes mysql-community-server

echo 'bind-address = 0.0.0.0' >> /etc/mysql/mysql.conf.d/mysqld.cnf
echo 'skip-character-set-client-handshake' >> /etc/mysql/mysql.conf.d/mysqld.cnf
echo 'collation_server=utf8_unicode_ci' >> /etc/mysql/mysql.conf.d/mysqld.cnf
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

cd /etc/nginx
mkdir certs
cd certs
sudo openssl genrsa -out "vagrantbox.key" 2048
sudo openssl req -new -key "vagrantbox.key" -out "vagrantbox.csr" -subj "/C=${SSL_C}/ST=${SSL_ST}/L=${SSL_L}/O=${SSL_O}/OU=${SSL_O}/CN=science/emailAddress=${EMAIL}"
sudo openssl x509 -req -days 365 -in "vagrantbox.csr" -signkey "vagrantbox.key" -out "vagrantbox.crt"


echo "...> Configure XDebug"
echo "; xdebug
zend_extension=xdebug.so
xdebug.remote_enable=1
xdebug.remote_connect_back=1" > /etc/php/${PHP_V}/mods-available/xdebug.ini
#ln -s /etc/php5/mods-available/mysqlnd.ini /etc/php5/fpm/conf.d/10-mysqlnd.ini
echo "...> Restart NGINX & PHP & MySQL"
/etc/init.d/nginx restart
/etc/init.d/php${PHP_V}-fpm restart
/etc/init.d/mysql restart

echo "....> Install programs"
apt-get install mc htop zip unzip mtr wget -y
mv /tmp/source-file/mc.config /root/.config
cp -R /root/.config /home/vagrant/
chown vagrant:vagrant -R /home/vagrant/.config
cp -R /root/.config /home/developer/
chown developer -R /home/developer/.config
echo "....> Install composer"
cd /tmp/source-file
wget -q https://getcomposer.org/composer.phar 
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
apt-get install git -y

apt-get autoremove
apt-get clean