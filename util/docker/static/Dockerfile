FROM library/node:8-alpine

RUN apk update \
    && apk add bash

# Install Bower & Grunt
RUN yarn global add gulp

COPY build_entrypoint.sh /
RUN chmod a+x /build_entrypoint.sh

# Define working directory.
WORKDIR /data

# Define default command.
CMD ["/build_entrypoint.sh"]