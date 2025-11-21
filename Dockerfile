FROM php:8.3-fpm

# -----------------------------
# 1. Instalar dependencias del sistema
# -----------------------------
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl zip pdo pdo_mysql soap

# -----------------------------
# 2. Instalar Node.js 20 LTS (requerido por Vite 7)
# -----------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# -----------------------------
# 3. Instalar Composer
# -----------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -----------------------------
# 4. Workspace
# -----------------------------
WORKDIR /var/www/html

# -----------------------------
# 5. Copiar archivos del proyecto
# -----------------------------
COPY . .

# -----------------------------
# 6. Instalar dependencias PHP
# -----------------------------
RUN composer install --no-dev --optimize-autoloader

# -----------------------------
# 7. Instalar dependencias JS y compilar Vite
# -----------------------------
RUN npm install && npm run build

# -----------------------------
# 8. Permisos
# -----------------------------
RUN chown -R www-data:www-data storage bootstrap/cache

# -----------------------------
# 9. Exponer puerto
# -----------------------------
EXPOSE 8080

# -----------------------------
# 10. Comando de inicio
# -----------------------------
CMD php artisan migrate --force && \
    php artisan db:seed --force --class=SuperAdminSeeder && \
    php artisan serve --host=0.0.0.0 --port=8080
