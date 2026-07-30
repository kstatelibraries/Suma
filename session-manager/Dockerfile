FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev libonig-dev zip unzip curl default-mysql-client apache2-utils && \
    docker-php-ext-install pdo_mysql mbstring zip && \
    a2enmod rewrite && \
    rm -rf /var/lib/apt/lists/*

# Copy local suma-session-manager code into image at /app/suma-session-manager
COPY --chown=www-data:www-data . /app/suma-session-manager

# Set working dir
WORKDIR /app/suma-session-manager

# Entrypoint & startup script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
