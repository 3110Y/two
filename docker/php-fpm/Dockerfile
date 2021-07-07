FROM php:8.0.3-fpm-buster
RUN apt-get update
RUN apt-get install -y git
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions http pdo_mysql zip
RUN docker-php-ext-install pdo
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

ADD https://github.com/ufoscout/docker-compose-wait/releases/download/2.7.3/wait /wait
RUN chmod +x /wait

ARG UID=1000
ARG GID=1000
ENV UID=${UID}
ENV GID=${GID}
RUN getent passwd $UID || (groupadd --gid $GID user && useradd --uid $UID --gid user --shell /bin/bash --create-home user)
USER $UID

# Install & migrate
CMD php-fpm

EXPOSE 9000
