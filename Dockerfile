FROM php:8.1-apache

# Install dependencies.
# apache2-utils supplies htpasswd, used to protect the Session Manager.
RUN apt-get update && apt-get install -y \
    libzip-dev libonig-dev zip unzip curl default-mysql-client apache2-utils && \
    docker-php-ext-install pdo_mysql mbstring zip && \
    a2enmod rewrite remoteip && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    rm -rf /var/lib/apt/lists/*

# Pin the container and PHP to UTC. All timestamps are stored in UTC; conversion
# to a local timezone happens only at display time (see SUMA_ANALYTICS_TIMEZONE).
# Relying on PHP's implicit UTC default is fragile, so declare it explicitly.
ENV TZ=UTC
RUN ln -snf /usr/share/zoneinfo/UTC /etc/localtime && echo "UTC" > /etc/timezone && \
    printf 'date.timezone = UTC\n' > /usr/local/etc/php/conf.d/timezone.ini

# Copy local suma code into image at /app/suma.
# This includes session-manager/, which is served from the same vhost.
COPY --chown=www-data:www-data . /app/suma

# Set working dir
WORKDIR /app/suma

# Create web root dirs (will be symlinked)
RUN mkdir -p /var/www/html/suma/

# Entrypoint & startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
