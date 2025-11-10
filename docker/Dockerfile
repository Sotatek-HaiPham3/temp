FROM php:7.3-apache

# RUN apt-get update && apt-get install software-properties-common

RUN apt-get update &&\
    apt-get install apt-utils curl gnupg -y &&\
    curl -sL https://deb.nodesource.com/setup_10.x | bash - &&\
    mkdir -p /usr/share/man/man1 &&\
    apt-get install nodejs python make g++ git unzip zip libzip-dev libcurl4-openssl-dev libc-client-dev libkrb5-dev autoconf default-jre-headless cron p7zip-full vim zlib1g-dev mariadb-client libpng-dev supervisor -y &&\
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --1 &&\
    docker-php-ext-configure gd --with-png-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) gd &&\
    docker-php-ext-install pdo_mysql bcmath curl opcache zip &&\
    cd /etc/apache2/mods-enabled &&\
    ln -s ../mods-available/rewrite.load ./ &&\
    ln -s /dev/stdout /var/log/apache2/access_atslp.log &&\
    ln -s /dev/stdout /var/log/apache2/access_crmlp.log &&\
    ln -s /dev/stdout /var/log/apache2/access_firstlp.log &&\
    ln -s /dev/stdout /var/log/apache2/access_top.log &&\
    ln -s /dev/stderr /var/log/apache2/error_atslp.log &&\
    ln -s /dev/stderr /var/log/apache2/error_crmlp.log &&\
    ln -s /dev/stderr /var/log/apache2/error_firstlp.log &&\
    ln -s /dev/stderr /var/log/apache2/error_top.log

RUN apt-get install ffmpeg -y

# RUN npm install pm2 -g

RUN touch /usr/local/etc/php/conf.d/memory_limit.ini \
    && echo "memory_limit = -1;" >> /usr/local/etc/php/conf.d/memory_limit.ini

RUN touch /usr/local/etc/php/conf.d/uploads.ini \
    && echo "upload_max_filesize = 10M;" >> /usr/local/etc/php/conf.d/uploads.ini

RUN touch /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M;" >> /usr/local/etc/php/conf.d/uploads.ini

COPY ./supervisord.conf /etc/supervisord.conf

COPY ./apache2.conf /etc/apache2/apache2.conf
COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./run.sh /usr/local/bin/run.sh
RUN echo '* * * * * www-data /usr/local/bin/php /var/www/gamelancer-api/artisan schedule:run >> /dev/null 2>&1' >> /etc/crontab
RUN chmod 755 /usr/local/bin/run.sh

WORKDIR /var/www/gamelancer-api

# The way run on Linux
CMD ["/usr/local/bin/run.sh"]

# The way run on Windows
# RUN rm -rf /var/run/apache2/
# CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
