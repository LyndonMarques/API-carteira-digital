FROM php:8.3-fpm

# Sincroniza o usuário www-data do container com o usuário do seu Ubuntu (UID 1000)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Instala a ferramenta facilitadora de extensões
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Instala os módulos PHP necessários e o Composer
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql redis bcmath mbstring xml curl zip opcache pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html