#FROM smartnetworks/base:apache-php
#FROM docker.sysdomain.local/base/apache-php
FROM php:8-fpm-alpine3.13

#########################################################################################################
## BEGIN Application
#########################################################################################################


RUN docker-php-ext-install pdo_mysql && \
    echo "expose_php = off" >> /usr/local/etc/php/conf.d/php.ini && \
    echo "upload_max_filesize = 25M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "always_populate_raw_post_data=-1" >> /usr/local/etc/php/conf.d/php.ini

ENV http_proxy ""
ENV https_proxy ""

COPY conf/*.crt /usr/local/ssl/crt/
COPY conf/*.key /usr/local/ssl/key/
COPY conf/httpd.conf* /usr/local/apache2/conf/
COPY conf/ssl.conf /usr/local/apache2/conf/extra/httpd-ssl.conf
COPY html /usr/local/apache2/htdocs
COPY conf/entrypoint.sh /usr/bin/entrypoint.sh
RUN cd /usr/local/apache2/conf && \
    cd /usr/local/apache2/htdocs && \
    rm -f index.html && \
    find /usr/local/apache2/htdocs -type d -exec chmod 755 {} \; && \
    find /usr/local/apache2/htdocs -type f -exec chmod 644 {} \; && \
    echo '#!/bin/bash' > /opt/passphrase.sh && \
    echo 'echo SPSmartNetworks' >> /opt/passphrase.sh && \
    chmod +x /opt/passphrase.sh

CMD ["entrypoint.sh"]
