FROM php:8.1-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    mysql-client \
    php-cli \
    php-pdo_mysql \
    php-json \
    php-mbstring \
    php-xml \
    php-openssl \
    php-curl \
    php-tokenizer \
    php-session

# Install cron
RUN apk add --no-cache busybox-cron

# Add crontab file
COPY docker/php/crontab /etc/crontabs/root

# Give execution rights on the cron job
RUN chmod 0644 /etc/crontabs/root

# Create a log file to be able to run tail -f
RUN touch /var/log/cron.log

# Run cron in the background
CMD crond -f -L /var/log/cron.log & php-fpm

RUN docker-php-ext-install pdo_mysql

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

EXPOSE 9000

CMD ["php-fpm"]
