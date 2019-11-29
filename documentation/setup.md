### log in as root

`adduser jason`

`sudo su jason`

`cd ~`

`mkdir .ssh`

`chmod 0700 .ssh`

### use PuTTYgen to create/save public/private keys - RSA 4096
### add private key to Pageant

`vim .ssh/authorized_keys`

### copy in public key

`addgroup devs`

`usermod -a -G sudo jason`

`usermod -a -G devs jason`

`exit`

### log in as jason

### make sure we're up to date with Ubuntu and its repositories

`sudo apt-get update && sudo apt-get ugrade`

### installation of Apache 2.4+, PHP 7.2+, MySQL 5.7 or later...

`sudo apt-get install apache2`

`sudo apt-get install php-fpm`

`sudo apt-get install mysql-server`

### secure mysql, per https://linuxize.com/post/how-to-install-mysql-on-ubuntu-18-04/

`sudo mysql_secure_installation`

### assign root password, and remove default/test setups

### install utilities, libraries and modules

`sudo apt-get install p7zip-full unzip php-pear imagemagick libcurl4 libgd3 libxpm4 libgd-tools`

`sudo apt-get install php-xml php-curl php-gd`

`sudo apt-get install php-xsl php-sqlite3 php-xdebug php-imagick php-ssh2 php-mysql php-soap php-zip php-bcmath php-intl php-mbstring`

`sudo apt-get install memcached php-memcached`

`sudo usermod -a -G www-data jason`

### reboot
### log in as jason

`sudo apt-get update`

`sudo apt-get install build-essential tcl`

### previously installed redis here - skipping since we don't actively use it for anything, but the build tools installed above should be generally useful

### optionally install nginx for page caching - not gonna set it up yet, first pass instructions here: https://www.nginx.com/blog/nginx-caching-guide/

### copy CK CA certs & etc to /root/ca

### copy SSL certificates into /etc/apache2/ssl

`sudo mkdir /etc/apach2/ssl`

`sudo chmod 0700 /etc/apache2/ssl`

### create key/csr/crt/ca-bundle files for current certificate - root access only on key
### copy latest ca-bundle.crt (?)

### create apache vhosts and other support structure

`cd /var/www`

`sudo rm -r html`

`sudo mkdir vhosts`

`sudo mkdir logs`

`sudo mkdir node`

`sudo chown www-data:www-data logs`

`sudo chgrp www-data vhosts`

`sudo chmod 0777 vhosts`

### create vhost directory per intended vhost

`cd vhosts`

`mkdir [default vhost dir]`

`chown www-data:www-data [default vhost dir]`

`mkdir [my vhost dir]`

`chgrp www-data [my vhost dir]`

### set up phpmyadmin

`mkdir awsdb`

`chown www-data:www-data awsdb`

### import phpmyadmin code to awsdb

`cd awsdb`

`cp config.sample.inc.php config.inc.php`

### set up server definitions in config file

### init DB users & databases

`sudo mysql`

`mysql> GRANT ALL PRIVILEGES ON *.* TO 'dev'@'localhost' IDENTIFIED BY '[password]';`

`mysql> GRANT ALL PRIVILEGES ON *.* TO 'ck-production'@'%' IDENTIFIED BY '[password]';`

`mysql> CREATE DATABASE [DB for my vhost];`

`mysql> exit;`

`mysql -udev -p [DB for my vhost] < migrate-db.sql`

### update php.ini

### install/remove apache modules

`sudo a2enmod expires headers rewrite slotmem_shm socache_shmcb ssl vhost_alias http2`

### necessary for http2: https://techwombat.com/enable-http2-apache-ubuntu-16-04/

`sudo a2enmod proxy_fcgi setenvif`

`sudo a2enconf php7.2-fpm`

`sudo a2dismod mpm_prefork`

`sudo a2enmod mpm_event`

`sudo service php7.2-fpm restart`

### previously installed modules *not* installed in this step - maybe later:
### lbmethod_byrequests proxy proxy_balancer proxy_html proxy_http xml2enc

### set up generic apache configs
### set up necessary apache module configs
### set up apache config at /etc/apache2/sites-available/*.cablesandkits.com.conf
### defaults for all of this at https://github.com/CablesAndKits/aws-devops

`sudo a2ensite [main site config]`

`sudo a2ensite paymentsvc.cablesandkits.com`

`sudo ufw allow http`

`sudo ufw allow https`

`sudo service apache2 restart`

### set up git repository

`cd [my vhost dir]`

`git init`

`git remote add ck_master https://github.com/CablesAndKits/Matrix-oSc.git`

`git pull ck_master master`

### copy untracked files:

`/.htaccess`

`/.gitignore`

`/admin/.htaccess`

`/config/*`

`/includes/configure.php`

`/admin/includes/configure.php`

### create empty folders:

`/feeds`

`/includes/templates/cache`

`/admin/images/fedex`

`/admin/images/fedex2`

`/admin/images/return-labels`

`/admin/data_management`

### copy dev-only files:

`/testsuite.php`

`/includes/tests/*`

`/*.dev.*`

`/admin/dev-testing.php`

`/includes/lib-tools/ck_test_group.class.php`

`/includes/templates/page-ck_test_group.mustache.html`

