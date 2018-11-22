FROM nginx:1.14

ARG APP_SECRET
ARG DB_USER
ARG DB_PASSWORD
ARG DB_NAME
ARG RABBITMQ_USER
ARG RABBITMQ_PASSWORD

ENV APP_SECRET=$APP_SECRET
ENV DB_USER=$DB_USER
ENV DB_PASSWORD=$DB_PASSWORD
ENV DB_NAME=$DB_NAME
ENV RABBITMQ_USER=$RABBITMQ_USER
ENV RABBITMQ_PASSWORD=$RABBITMQ_PASSWORD

RUN apt-get -qq update && apt-get install -qq -y wget
RUN mkdir -p var/log/nginx-app

ENV DOCKERIZE_VERSION v1.2.0
RUN wget -q https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

COPY site.tmpl /etc/nginx/conf.d/site.tmpl

RUN dockerize -template /etc/nginx/conf.d/site.tmpl:/etc/nginx/conf.d/default.conf
