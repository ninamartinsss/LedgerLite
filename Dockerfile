FROM php:8.3-cli-alpine
RUN docker-php-ext-install pdo_sqlite
WORKDIR /app
COPY . .
RUN mkdir -p /app/data && chown -R www-data:www-data /app/data
USER www-data
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]