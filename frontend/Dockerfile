FROM library/node:lts-alpine

RUN apk update \
    && apk add bash python make curl g++

RUN mkdir -p /data/frontend/node_modules /data/resources/locale /data/web/static \
    && chown -R node:node /data

RUN USER=node && \
    GROUP=node && \
    curl -SsL https://github.com/boxboat/fixuid/releases/download/v0.5.1/fixuid-0.5.1-linux-amd64.tar.gz | tar -C /usr/local/bin -xzf - && \
    chown root:root /usr/local/bin/fixuid && \
    chmod 4755 /usr/local/bin/fixuid && \
    mkdir -p /etc/fixuid && \
    printf "user: $USER\ngroup: $GROUP\n" > /etc/fixuid/config.yml

COPY build_entrypoint.sh /
RUN chmod a+x /build_entrypoint.sh

# Define working directory.
WORKDIR /data/frontend

# Define working user.
USER node:node

VOLUME /data/frontend/node_modules

# Define default command.
ENTRYPOINT ["fixuid"]
CMD ["npm", "run", "dev"]
