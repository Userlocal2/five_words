echo "...................................................."
echo "...................................................."
echo "...................> Start sepagateway Configure"
echo "...................................................."
echo "...................> Prepering sepagateway Environment"
echo ".....> Install..PHP..7-1"
sed -i "s/PermitRootLogin without-password/PermitRootLogin yes/" /etc/ssh/sshd_config
sed -i "s/PasswordAuthentication no/PasswordAuthentication yes/" /etc/ssh/sshd_config
/etc/init.d/ssh restart
mkdir /root/.ssh
cat /tmp/source-file/key/id_rsa.pub >> /root/.ssh/authorized_keys
useradd -g root -G sudo -s /bin/bash -d /home/developer -m  developer
echo "developer:payb"|chpasswd
mkdir /var/log/php
apt-get update
apt-get install apt-transport-https lsb-release ca-certificates -y
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
apt-get update
apt-get install php7.1 php7.1-fpm php7.1-dev php7.1-intl php7.1-mbstring php7.1-sqlite php7.1-curl php7.1-xml php7.1-mysqlnd php7.1-pgsql php7.1-xdebug php7.1-bcmath -y

pecl install timezonedb
echo "error_log = php_errors.log" >> /etc/php/7.1/fpm/php.ini
echo "extension=timezonedb.so" >> /etc/php/7.1/fpm/php.ini
echo "error_log = php_errors.log" >> /etc/php/7.1/cli/php.ini
echo "extension=timezonedb.so" >> /etc/php/7.1/cli/php.ini
echo "....> Install..NGINX..1-6"
apt-get remove apache2 -y
apt-get install nginx -y



#echo "....> Install MySQL.."
#echo 'deb http://repo.mysql.com/apt/debian/ jessie mysql-apt-config' >> /etc/apt/sources.list.d/mysql.list
#echo 'deb http://repo.mysql.com/apt/debian/ jessie mysql-5.6' >> /etc/apt/sources.list.d/mysql.list
#echo 'deb http://repo.mysql.com/apt/debian/ jessie mysql-tools' >> /etc/apt/sources.list.d/mysql.list
#echo 'deb-src http://repo.mysql.com/apt/debian/ jessie mysql-5.6' >> /etc/apt/sources.list.d/mysql.list
#apt-get update
#export DEBIAN_FRONTEND=noninteractive
#
#mysqlpassword="root"
#debconf-set-selections <<< "mysql-server mysql-server/root_password password $mysqlpassword"
#debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $mysqlpassword"
#apt-get install -q -y  --force-yes mysql-community-server
#
#echo 'bind-address = 0.0.0.0' >> /etc/mysql/mysql.conf.d/mysqld.cnf
#echo 'skip-character-set-client-handshake' >> /etc/mysql/mysql.conf.d/mysqld.cnf
#echo 'collation_server=utf8_unicode_ci' >> /etc/mysql/mysql.conf.d/mysqld.cnf
#echo 'character_set_server=utf8' >> /etc/mysql/mysql.conf.d/mysqld.cnf
#service mysql restart
#
#if [ -f /var/lib/mysql/spp_db/db.opt ]
#then
#	echo "XXXXXXXXX: Database alredy exist"
#else
#    echo "XX - Execute query"
#    echo "XX - Set rights to local"
#    mysql -e "SET PASSWORD FOR 'root'@'localhost' = PASSWORD(''); FLUSH PRIVILEGES;"
#    echo "XX - Set rights to %"
#    mysql -e "CREATE USER 'root'@'%' IDENTIFIED BY '$mysqlpassword'; FLUSH PRIVILEGES;"
#    echo "XX - Set grants"
#    mysql -uroot -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' WITH GRANT OPTION;"
#    mysql -uroot -e "GRANT ALL PRIVILEGES ON *.* TO 'vagrant'@'%' IDENTIFIED BY 'vagrant' WITH GRANT OPTION;"
#    mysql -uroot -e "GRANT ALL PRIVILEGES ON *.* TO 'vagrant'@'localhost' IDENTIFIED BY 'vagrant' WITH GRANT OPTION;"
#    mysql -uroot -e "CREATE DATABASE spp_db;"
#    mysql -uroot -e "CREATE DATABASE test_spp_db;"
#fi
#
#service mysql restart

echo "...> Configure NGINX"
#rm /etc/nginx/sites-enabled/default
#mv /tmp/source-file/nginx/sepagateway.local.conf /etc/nginx/sites-enabled/
#mv /tmp/source-file/nginx/* /etc/nginx/


#mkdir /etc/ssl/sepagateway.local
#openssl req -x509 -nodes -days 365 -newkey rsa:2048 -nodes -subj "/C=UA/ST=Dnepr/L=Dnepr/O=Paybtqtd/CN=sepagateway.local/emailAddress=admin@sepagateway.local" -keyout /etc/ssl/sepagateway.local/sepagateway.local.key -out /etc/ssl/sepagateway.local/sepagateway.local.csr

#echo "...> Configure PHP-FPM"
#mv /tmp/source-file/php/fpm /etc/php/7.1/fpm


echo "...> Configure XDebug"
echo "; xdebug
zend_extension=xdebug.so
xdebug.remote_enable=1
xdebug.remote_connect_back=1" > /etc/php/7.1/mods-available/xdebug.ini
#ln -s /etc/php5/mods-available/mysqlnd.ini /etc/php5/fpm/conf.d/10-mysqlnd.ini
echo "...> Restart NGINX & PHP & MySQL"
service nginx restart
service php7.1-fpm restart
#service mysql restart

echo "....> Install programs"
apt-get install mc htop zip unzip mtr wget -y
#mkdir /var/log/payboutique
#chown www-data:www-data /var/log/payboutique
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


