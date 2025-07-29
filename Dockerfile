FROM php:8.2-fpm

# نصب پیش‌نیازها
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    libgmp-dev \
    zip \
    unzip \
    git \
    curl \
    nano \
    wget \
    ca-certificates \
    gnupg2 \
    libsodium-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# نصب اکستنشن‌های PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    curl \
    gmp \
    sodium

# نصب و فعال‌سازی mongodb extension
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# نصب Composer از ایمیج رسمی
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# **کپی کل پروژه به کانتینر قبل از نصب composer**
COPY . .

# نصب وابستگی‌ها با composer
RUN composer install --no-dev --no-interaction --optimize-autoloader

# ساخت دایرکتوری‌های لازم و تنظیم مجوزها
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
