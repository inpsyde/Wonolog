FROM wordpress:cli-php8.3

USER root

RUN set -eux; apk update && apk add git

COPY install-composer.sh /usr/bin/install-composer.sh
RUN chmod +x /usr/bin/install-composer.sh && /usr/bin/install-composer.sh && mv composer.phar /usr/local/bin/composer

COPY wait-for.sh /usr/bin/wait-for.sh
RUN chmod +x /usr/bin/wait-for.sh

RUN git config --global --add safe.directory /var/www/html

WORKDIR /var/www/html
