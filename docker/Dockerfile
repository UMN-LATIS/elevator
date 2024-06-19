FROM php:7.4-apache


RUN apt update && apt install -y unzip git libpq-dev docker && apt clean
RUN pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-install pgsql pdo_pgsql pdo
RUN pecl install mongodb && docker-php-ext-enable mongodb
RUN a2enmod rewrite && a2enmod headers
COPY get-docker.sh /root/
RUN chmod +x /root/get-docker.sh
RUN /root/get-docker.sh
COPY commands/* /usr/local/bin/
RUN mkdir /scratch
RUN mkdir -p /tmp
RUN mkdir -p /opt
COPY ../ /var/www/html/
WORKDIR /var/www/html
ARG DOCKER_SCRATCH_DIR
ENV DOCKER_SCRATCH_DIR $DOCKER_SCRATCH_DIR
# don't run this in prod
RUN sed -i "s|/scratch:|$DOCKER_SCRATCH_DIR:|g" /usr/local/bin/*
RUN sed -i 's/docker run/docker run --platform=linux\/amd64/g' /usr/local/bin/*