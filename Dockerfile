FROM php:7.4-apache as prod
ENV TZ="Europe/Paris"

WORKDIR /var/www/html
RUN apt-get update -qq && \
    apt-get install -qy \
    gnupg 

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /usr/share/keyrings/yarn-archive-keyring.gpg - && \
    echo "deb [signed-by=/usr/share/keyrings/yarn-archive-keyring.gpg] https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list

RUN apt-get update -qq && \
    apt-get install -qy \
    git \
    gnupg \
    libicu-dev \
    libzip-dev \
    unzip \
    zip \
    zlib1g-dev \
    libpng-dev \
    libwebp-dev libjpeg62-turbo-dev libpng-dev libxpm-dev libfreetype6-dev unoconv\
    yarn  && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*


# PHP Extensions
RUN docker-php-ext-configure zip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) intl opcache pdo_mysql zip gd

RUN pecl install redis && \
    docker-php-ext-enable redis
COPY ./docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/php.ini /usr/local/etc/php/conf.d/php.ini

RUN a2enmod rewrite 


# WKHTMLTOPDF
RUN apt-get update -qq && \
    apt-get install -y fontconfig libfreetype6 libjpeg62-turbo libpng16-16 xfonts-75dpi xfonts-base && \
    # apt-get install -y fontconfig xvfb x11-xkb-utils xfonts-100dpi xfonts-75dpi xfonts-scalable && \
    apt-get install -y libxrender1 && \
    apt-get install -y wget && \
    wget -q -O wkhtmltox.deb https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.bullseye_amd64.deb && \
    dpkg -i wkhtmltox.deb && \
    apt-get install -y -f && \
    rm wkhtmltox.deb

COPY --chown=www-data:www-data  . /var/www/html
COPY --chown=www-data:www-data  docker/symfony.env /var/www/html/.env 
RUN chmod a+rwX /var/www
RUN /var/www/html/docker/install_composer.sh
RUN mv composer.phar /usr/local/bin/composer
RUN mkdir -p /var/www/html/var/cache/ && chown www-data:www-data /var/www/html/var/cache/
RUN mkdir -p /var/www/html/var/log/ && chown www-data:www-data /var/www/html/var/log/
RUN mkdir -p /var/www/html/var/upload/ && chown www-data:www-data /var/www/html/var/upload/
RUN mkdir /var/www/.symfony && chown www-data:www-data /var/www/.symfony
RUN mkdir /var/www/.cache && chown www-data:www-data /var/www/.cache
RUN mkdir /var/www/.yarn && chown www-data:www-data /var/www/.yarn
RUN touch /var/www/.yarnrc && chown www-data:www-data /var/www/.yarnrc
## Install NPM
RUN apt-get update -qq && apt-get install -y nodejs npm
RUN npm install -g sass
USER www-data
RUN APP_ENV=prod composer install  --no-dev --prefer-dist --no-scripts
#RUN php bin/console assets:install
RUN yarn install 
RUN rm .env
#RUN yarn build 



copy docker/entrypoint.sh /entrypoint.sh
ENTRYPOINT [ "/entrypoint.sh" ]

COPY docker/symfony.env /var/www/html/.env
CMD ["apache2-foreground"] 


From prod as dev
USER root
RUN --mount=type=cache,id=apt-cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,id=apt-lib,target=/var/lib/apt,sharing=locked \
    --mount=type=cache,id=debconf,target=/var/cache/debconf,sharing=locked \
    apt update && \
    apt install -y curl

RUN USER=www-data && \
    GROUP=www-data && \
    curl -SsL https://github.com/boxboat/fixuid/releases/download/v0.6.0/fixuid-0.6.0-linux-amd64.tar.gz | tar -C /usr/local/bin -xzf - && \
    chown root:root /usr/local/bin/fixuid && \
    chmod 4755 /usr/local/bin/fixuid && \
    mkdir -p /etc/fixuid && \
    printf "user: $USER\ngroup: $GROUP\n" > /etc/fixuid/config.yml

