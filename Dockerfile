FROM php:8.3-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl zip pdo pdo_mysql

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Directorio de trabajo dentro del contenedor
WORKDIR /var/www/html

# Copiar todos los archivos del proyecto
COPY . .

# Instalar dependencias PHP de Laravel
RUN composer install --no-dev --optimize-autoloader

# Instalar dependencias JS y compilar assets (Vite)
RUN npm install && npm run build

# Dar permisos a storage y cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Puerto donde correrá Laravel
EXPOSE 8080

# Comando de arranque en Railway
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080
