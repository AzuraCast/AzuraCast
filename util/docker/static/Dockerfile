FROM library/node

# Install Bower & Grunt
RUN npm install -g bower grunt grunt-cli && \
    echo '{ "allow_root": true }' > /root/.bowerrc

# Define working directory.
WORKDIR /data

COPY build_entrypoint.sh /data
COPY package.json /data

RUN npm install --loglevel warn

RUN chmod a+x build_entrypoint.sh

# Define default command.
CMD ["/data/build_entrypoint.sh"]