FROM httpd:2.4.37

ARG WEB_USER
ARG WEB_GROUP
ARG APACHE_ROOT_DIR
ARG APACHE_EXPOSED_PORT
ARG APACHE_SSL_EXPOSED_PORT

RUN apt-get update ; \
    # Set system timezone based on environment variable
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone \
    # install required packages
    && apt-get install -yq --no-install-recommends \
        p7zip-full unzip php-pear imagemagick \
        libgd3 libxpm4 libgd-tools \
        build-essential tcl \
        unzip \
    # cleaning...
    && apt-get -y autoremove \
    && apt-get clean \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY ./server.crt ${APACHE_ROOT_DIR}/conf/server.crt
COPY ./server.key ${APACHE_ROOT_DIR}/conf/server.key
#COPY ./config/httpd.conf ${APACHE_ROOT_DIR}/conf/httpd.conf
#COPY ./config/extra/httpd-vhosts.conf ${APACHE_ROOT_DIR}/conf/extra/httpd-vhosts.conf

#USER ${WEB_USER}
#ADD ./Matrix-oSc.tar.gz ${PHP_APP_DIR}

USER root
EXPOSE ${APACHE_EXPOSED_PORT}
EXPOSE ${APACHE_SSL_EXPOSED_PORT}
