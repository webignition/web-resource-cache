FROM phpmyadmin/phpmyadmin:4.8.3

ENV DOCKERIZE_VERSION v1.2.0
RUN wget https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

CMD dockerize -wait tcp://mysql:3306 supervisord -n
