FROM nginx:alpine

RUN rm -f /etc/nginx/conf.d/default.conf
COPY ./nginx_azuracast.conf /etc/nginx/conf.d/azuracast.conf

RUN apk update && \
    apk add openssl

RUN mkdir -p /etc/nginx/ssl/
RUN openssl req -new -nodes -x509 -subj "/C=US/ST=Texas/L=Austin/O=IT/CN=localhost" -days 3650 -keyout /etc/nginx/ssl/server.key -out /etc/nginx/ssl/server.crt -extensions v3_ca

RUN sed -i "s/sendfile        on/sendfile off/" /etc/nginx/nginx.conf