# NomReNom - Cloud Backend Server Dockerfile
FROM php:8.2-apache

# Install system dependencies and PHP extensions for MySQL, PDO, and ZIP
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-install pdo pdo_mysql mysqli zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy backend PHP application into web root
COPY backend/ /var/www/html/

# Copy frontend static assets so the server can serve both frontend & backend
COPY frontend/ /var/www/html/frontend/

# Set correct ownership and permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose HTTP port
EXPOSE 80

# Dynamically bind to cloud $PORT (Render, Railway, Heroku) or default to 80
CMD ["sh", "-c", "sed -i \"s/Listen 80/Listen ${PORT:-80}/g\" /etc/apache2/ports.conf && sed -i \"s/:80/:${PORT:-80}/g\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
