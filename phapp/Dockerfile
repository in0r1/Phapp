FROM php:8.3-apache

# Install MySQL server and PHP MySQL extensions
RUN apt-get update \
    && apt-get install -y default-mysql-server \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy PHP application
COPY src/ /var/www/html/

# Copy startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80 3306

CMD ["/start.sh"]
