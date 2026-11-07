FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && docker-php-ext-install curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY index.php /var/www/html/index.php
COPY health.php /var/www/html/health.php

EXPOSE 80

CMD ["apache2-foreground"]
