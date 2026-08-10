# syntax=docker/dockerfile:1

FROM php:8.3-apache

# curl is for the healthcheck below; it is not in the base image. fileinfo,
# which backs the MIME sniffing in Uploader, is bundled and enabled already —
# re-installing it fails the build, so it is only verified here.
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/* \
    && php -m | grep -q '^fileinfo$' \
    && a2enmod rewrite headers

# The document root is public/ only. src/, data/ and storage/ sit beside it and
# are never reachable over HTTP — that is what stops an uploaded file from being
# requested, and interpreted, directly.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-fileshare.ini

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html

# Runtime state must be writable by the server user. Doing this in the image
# means a fresh container works on first boot instead of failing its first
# upload with a permissions error.
RUN mkdir -p /var/www/html/data /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/storage \
    && chmod -R 775 /var/www/html/data /var/www/html/storage

# Application code is owned by root and readable, not writable, by the server —
# a compromised upload path cannot then rewrite the app itself.
RUN chown -R root:www-data /var/www/html/src /var/www/html/public \
    && chmod -R 750 /var/www/html/src /var/www/html/public

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -fsS http://localhost/login.php > /dev/null || exit 1
