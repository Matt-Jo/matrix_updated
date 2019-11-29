FROM php:7.2-fpm
 
ARG WEB_USER
ARG WEB_GROUP
ARG PHP_ROOT_DIR
ARG PHP_WITH_XDEBUG
ARG PHP_APP_DIR

RUN apt-get update && \
    docker-php-ext-install mysqli pdo pdo_mysql opcache bcmath && \
    if [ $PHP_WITH_XDEBUG = "true" ] ; then \
	    pecl install xdebug; \
	    docker-php-ext-enable xdebug ; \
	    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
	    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
	    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
	    echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.remote_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.remote_port=9001" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.var_display_max_data=1024" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.default_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.remote_autostart=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
        ## This is very helpful when something fails and you need a clue to start looking...
        # echo "xdebug.remote_log=\"/tmp/xdebug.log\"" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
        export XDEBUG_CONFIG="idekey=PHPSTORM"; \
	fi ; \
    # memcached php modules setup instructions at docker hub do not work
    # see: https://stackoverflow.com/questions/39343774/install-php7-fpm-and-memcached-with-docker#answers
    apt-get update && apt-get install -y \
        libmemcached11 \
        libmemcachedutil2 \
        libmemcached-dev \
        libz-dev \
        git \
        curl && \
    cd /root && \
    git clone -b php7 https://github.com/php-memcached-dev/php-memcached && \
    cd php-memcached && \
    phpize && \
    ./configure && \
    make && \
    make install && \
    cd .. && \
    rm -rf  php-memcached && \
    echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini && \
    apt-get remove -y build-essential libmemcached-dev libz-dev && \
    apt-get remove -y libmemcached-dev libz-dev && \
    apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* && \
    apt-get clean && \
    usermod -u 1000 ${WEB_USER} && \
    groupmod -g 1000 ${WEB_GROUP}

#RUN curl -sS https://getcomposer.org/installer | \
#    php -- --install-dir=/usr/bin/ --filename=composer

#USER ${WEB_USER}

#COPY ./www.conf ${PHP_ROOT_DIR}/php-fpm.d/www.conf
#COPY ./config/webapp/includes/configure.php ${PHP_APP_DIR}/includes/configure.php
#COPY ./config/webapp/config ${PHP_APP_DIR}/config
#COPY ./config/webapp/admin/includes/configure.php ${PHP_APP_DIR}/admin/includes/configure.php

#ADD ./Matrix-oSc.tar.gz ${PHP_APP_DIR}

#WORKDIR ${PHP_APP_DIR}/includes/engine
#RUN composer install --no-scripts --no-autoloader && \
#    composer dump-autoload --optimize && \
#    composer run-script post-install-cmd

