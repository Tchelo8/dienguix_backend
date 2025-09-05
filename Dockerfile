FROM php:8.2-apache

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    git \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_pgsql pgsql zip intl

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Permettre à Composer de fonctionner en tant que root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Configuration Apache
RUN a2enmod rewrite

# Configuration du document root pour Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Copier TOUT le code
COPY . .

# Installation de Composer SANS les scripts auto (pour éviter cache:clear pendant le build)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Créer les dossiers nécessaires avec bonnes permissions
RUN mkdir -p var/cache var/log
RUN chmod 777 var/cache var/log

# Permissions finales
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
