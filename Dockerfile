FROM ubuntu:latest

RUN apt-get update && \
    apt-get install -y supervisor

RUN mkdir -p /var/log/supervisor
RUN mkdir -p /var/azuracast/servers/shoutcast2
RUN mkdir -p /var/azuracast/servers/icecast2

RUN mkdir /var/log/shoutcast

# Download Shoutcast 2
WORKDIR /var/azuracast/servers/shoutcast2

RUN apt-get update && \
    apt-get install -y wget && \
    wget http://download.nullsoft.com/shoutcast/tools/sc_serv2_linux_x64-latest.tar.gz && \
    tar -xzf sc_serv2_linux_x64-latest.tar.gz

# Download and build IceCast-KH
WORKDIR /var/azuracast/servers/icecast2

RUN apt-get update && \
    apt-get install -y libxml2 libxslt1-dev libvorbis-dev libssl-dev libcurl4-openssl-dev pkg-config && \
    wget https://github.com/karlheyes/icecast-kh/archive/icecast-2.4.0-kh5.tar.gz && \
    tar -xzf --strip-components=1 icecast-2.4.0-kh5.tar.gz && \
    ./configure && \
    make && \
    make install

ADD ./resources/error.mp3 /usr/local/share/icecast/web/error.mp3
ADD ./resources/status-json.xsl /usr/local/share/icecast/web/status-json.xsl
ADD ./resources/xml2json.xslt /usr/local/share/icecast/web/xml2json.xslt

# Build LiquidSoap
RUN apt-get update && \
    apt-get install -y opam libpcre3-dev libfdk-aac-dev libmad0-dev libmp3lame-dev libtag1-dev libfaad-dev libflac-dev pkg-config m4 && \
    opam init -y && \
    opam install -y taglib mad faad fdkaac lame vorbis.0.6.2 cry.0.4.1 flac liquidsoap.1.2.1

EXPOSE 9001
EXPOSE 8000-8999

CMD ["/usr/bin/supervisord"]