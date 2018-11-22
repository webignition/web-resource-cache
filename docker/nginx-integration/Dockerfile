FROM nginx:1.14

RUN apt-get -qq update && apt-get install -qq -y wget
RUN mkdir -p var/log/nginx-app

ENV DOCKERIZE_VERSION v1.2.0
RUN wget -q https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

COPY site.tmpl /etc/nginx/conf.d/site.tmpl

RUN dockerize -template /etc/nginx/conf.d/site.tmpl:/etc/nginx/conf.d/integration.conf
