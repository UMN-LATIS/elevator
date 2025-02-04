FROM php:8.4-fpm

RUN apt update && apt install -y unzip git libpq-dev docker libzip-dev && apt clean
RUN pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-install pgsql pdo_pgsql pdo zip
# RUN export CFLAGS="-D_DEFAULT_SOURCE"
# RUN export LDFLAGS="-lssl -lcurl"
RUN pecl install mongodb && docker-php-ext-enable mongodb
# RUN a2enmod rewrite && a2enmod headers
RUN docker-php-ext-install sockets
COPY docker/get-docker.sh /root/
RUN chmod +x /root/get-docker.sh
RUN /root/get-docker.sh
COPY docker/commands/* /usr/local/bin/
RUN mkdir /scratch
RUN mkdir -p /tmp
RUN mkdir -p /opt
COPY composer.json /var/www/html/
COPY composer.lock /var/www/html/

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN git config --global --add safe.directory /var/www/html
RUN composer install --ignore-platform-reqs

ARG DOCKER_SCRATCH_DIR
ENV DOCKER_SCRATCH_DIR $DOCKER_SCRATCH_DIR

COPY . /var/www/html/
WORKDIR /var/www/html
RUN chmod -R 777 /var/www/html/application/models/Proxies
# append "short_open_tag" to php.ini
COPY docker/php.ini "$PHP_INI_DIR/php.ini"


ARG DOCKER_ENVIRONMENT
ENV DOCKER_ENVIRONMENT $DOCKER_ENVIRONMENT
RUN if [ "$DOCKER_ENVIRONMENT" = "local" ]; then \
    sed -i "s|/scratch:|$DOCKER_SCRATCH_DIR:|g" /usr/local/bin/* && \
    sed -i 's/docker run/docker run --platform=linux\/amd64/g' /usr/local/bin/*; \
fi