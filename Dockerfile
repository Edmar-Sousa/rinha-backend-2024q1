FROM hyperf/hyperf:8.2-alpine-v3.18-swoole


ENV timezone="America/Sao_Paulo"


RUN set -ex \
    && php -v \
    && php -m \
    && php --ri swoole \

    # define the docker timezone
    && ln -sf /usr/share/zoneinfo/${timezone} /etc/localtime \
    && echo "${timezone}" > /etc/timezone


RUN set -ex \
    && apk --no-cache add php82-pdo_pgsql


WORKDIR /api

COPY . .

RUN composer install


EXPOSE 9501


CMD [ "php", "bin/hyperf.php", "start" ]

