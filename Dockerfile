FROM richarvey/nginx-php-fpm:latest

# Salin semua file project ke dalam server container
COPY . /var/www/html

# Atur environment bawaan untuk image
ENV TIMEDATE=Asia/Makassar
ENV ERRORS=0
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV RUN_SCRIPTS=1

# Buka port 80 untuk akses web/API
EXPOSE 80