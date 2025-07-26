FROM php:8.2-fpm

# نصب پیش‌نیازها و ابزارهای مورد نیاز
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
    zip \
    unzip \
    git \
    curl \
    nano \
    wget \
    ca-certificates \
    gnupg2 \
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
    curl

# نصب Redis و MongoDB از طریق PECL
RUN pecl install redis mongodb && docker-php-ext-enable redis mongodb

# نصب Composer از کانتینر رسمی
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# مسیر کاری
WORKDIR /var/www

# کپی کل پروژه
COPY . .

# نصب پکیج‌ها
RUN composer install --optimize-autoloader

# ایجاد دایرکتوری‌ها و تنظیم دسترسی‌ها
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# باز کردن پورت پیش‌فرض PHP-FPM
EXPOSE 9000

# اجرای PHP-FPM
CMD ["php-fpm"]
