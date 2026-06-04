FROM php:8.3-cli-alpine

RUN apk add --no-cache curl-dev \
    && docker-php-ext-install curl

WORKDIR /var/www/html

COPY . .

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/html"]
