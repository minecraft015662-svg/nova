FROM php:8.2-cli
RUN apt-get update && apt-get install -y libsqlite3-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo_sqlite curl
WORKDIR /app
COPY nexa-suite/ /app/
RUN mkdir -p storage config && chmod -R 777 storage config
CMD sh -c 'php bin/nexa install --no-interaction --site="Nexa Suite" --url="$PUBLIC_URL" --name="Admin" --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD"; php bin/nexa serve --host=0.0.0.0 --port=${PORT:-10000}'
