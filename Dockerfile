FROM php:8.4-apache-bookworm

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    DB_BUSY_TIMEOUT=5000 \
    SESSION_SECURE_COOKIE=true \
    LOG_CHANNEL=stderr

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libsqlite3-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && for extension in pdo_sqlite mbstring; do \
        if ! php -r "exit(extension_loaded('$extension') ? 0 : 1);"; then \
            docker-php-ext-install "$extension"; \
        fi; \
    done \
    && a2enmod rewrite \
    && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Options -Indexes\n    Require all granted\n</Directory>\nServerName localhost\n' > /etc/apache2/conf-available/stem-pathways.conf \
    && a2enconf stem-pathways

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && sed -i 's/\r$//' docker/render-start.sh \
    && install -m 0755 docker/render-start.sh /usr/local/bin/render-start

EXPOSE 10000
CMD ["/usr/local/bin/render-start"]
