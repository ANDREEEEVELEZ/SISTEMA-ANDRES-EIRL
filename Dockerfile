FROM php:8.3-fpm

# Instalar dependencias del sistema necesarias para XML, SOAP y ZIP
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl zip pdo pdo_mysql soap

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos al contenedor
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader

# Instalar dependencias JS y compilar Vite
RUN npm install && npm run build

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080
