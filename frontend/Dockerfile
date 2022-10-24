FROM library/node:18-alpine

RUN apk update \
    && apk add bash python3 make curl g++ shadow git

RUN groupdel dialout

RUN mkdir -p /data/frontend/node_modules /data/translations /data/web/static \
    && chown -R node:node /data

RUN USER=node && \
    GROUP=node && \
    curl -SsL https://github.com/boxboat/fixuid/releases/download/v0.5.1/fixuid-0.5.1-linux-amd64.tar.gz | tar -C /usr/local/bin -xzf - && \
    chown root:root /usr/local/bin/fixuid && \
    chmod 4755 /usr/local/bin/fixuid && \
    mkdir -p /etc/fixuid && \
    printf "user: $USER\ngroup: $GROUP\npaths:\n  - /\n  - /data/frontend/node_modules\n" > /etc/fixuid/config.yml

COPY ./entrypoint.sh /
RUN chmod a+x /entrypoint.sh

# Define working directory.
WORKDIR /data/frontend

# Define working user.
USER node:node

VOLUME /data/frontend/node_modules

ENV NODE_ENV=production
ENV HOME=/home/node

# Define default command.
ENTRYPOINT ["/entrypoint.sh"]
CMD ["bash"]
