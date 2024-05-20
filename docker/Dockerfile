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
RUN mkdir /opt
COPY . /opt/
WORKDIR /opt