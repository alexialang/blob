FROM webdevops/php-nginx:8.3-alpine AS base

ENV TZ=Europe/Paris
ENV fpm.global.log_level=error
ENV XDEBUG_MODE=off

RUN apk update && \
    apk --no-cache add pcre-dev ${PHPIZE_DEPS} && \
    apk add vim curl git openssh-client apk-cron busybox-extras-openrc libpq-dev icu-dev rabbitmq-c-dev libzip-dev zip libpng-dev libjpeg-turbo-dev busybox-suid && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure gd --enable-gd --with-jpeg --enable-shared && \
    docker-php-ext-install pdo pdo_pgsql intl zip gd && \
    docker-php-ext-enable pdo_pgsql amqp

# xdebug
RUN apk add --update linux-headers
RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug

RUN rm -rf /var/lib/nginx/logs

RUN apk upgrade --update \
    && apk add -U tzdata \
    && cp /usr/share/zoneinfo/Europe/Paris /etc/localtime \
    && apk del tzdata \
    && rm -rf \
    /var/cache/apk/*

WORKDIR /srv/app/
RUN chown application:application .

FROM base AS final

ARG GIT_TOKEN

USER application

COPY ./.docker/all/php/conf.d/custom.ini /opt/docker/etc/php/php.ini

COPY --chown=adimeo:adimeo ./composer.json ./composer.lock* /srv/app/

RUN composer config --global repositories.packagist composer https://packagist.org

COPY --chown=application:application . /srv/app/
RUN mkdir /srv/app/var/data -p

RUN composer install --no-interaction --optimize-autoloader

COPY .docker/all/nginx/custom.conf /opt/docker/etc/nginx/vhost.common.d/10-location-root.conf
COPY .docker/all/nginx/vhost.conf /opt/docker/etc/nginx/vhost.conf

ENV WEB_DOCUMENT_ROOT=/srv/app/public

USER root
