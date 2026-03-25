FROM php:8.3-fpm

# Instala dependências vitais do SO para o Composer funcionar sem engasgos
RUN apt-get update && apt-get install -y git unzip && apt-get clean && rm -rf /var/lib/apt/lists/*

# Sincroniza o usuário www-data com o Ubuntu host
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Instala extensões do PHP
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pdo_mysql redis bcmath mbstring xml curl zip opcache pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Cria o diretório do PsySH e transfere a posse para o www-data antes de trocar de usuário
RUN mkdir -p /var/www/.config/psysh && chown -R www-data:www-data /var/www/.config

# Define o usuário correto para não gerar arquivos como root na sua máquina
USER www-data