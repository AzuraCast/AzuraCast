FROM ubuntu:xenial

RUN apt-get update && \
    apt-get install -q -y curl

# Install supervisord
RUN mkdir -p /var/log/supervisor

RUN apt-get update && \
    apt-get install -q -y supervisor

ADD ./supervisord.conf /etc/supervisor/supervisord.conf

# Set up broadcast sections
RUN mkdir -p /var/azuracast/servers/shoutcast2 && \
    mkdir -p /var/azuracast/servers/icecast2 && \
    mkdir -p /var/azuracast/stations

RUN adduser --home /var/azuracast --disabled-password --gecos "" azuracast && \
    chown -R azuracast:azuracast /var/azuracast

# Download Shoutcast 2
WORKDIR /var/azuracast/servers/shoutcast2

RUN apt-get update && \
    apt-get install -y wget && \
    wget http://download.nullsoft.com/shoutcast/tools/sc_serv2_linux_x64-latest.tar.gz && \
    tar -xzf sc_serv2_linux_x64-latest.tar.gz

# Download and build IceCast-KH
WORKDIR /var/azuracast/servers/icecast2

RUN apt-get update && \
    apt-get install -y --no-install-recommends libxml2 libxslt1-dev libvorbis-dev libssl-dev libcurl4-openssl-dev \
     gcc pkg-config && \
    wget https://github.com/karlheyes/icecast-kh/archive/icecast-2.4.0-kh5.tar.gz && \
    tar --strip-components=1 -xzf icecast-2.4.0-kh5.tar.gz && \
    ./configure && \
    make && \
    make install

ADD ./error.mp3 /usr/local/share/icecast/web/error.mp3
ADD ./status-json.xsl /usr/local/share/icecast/web/status-json.xsl
ADD ./xml2json.xslt /usr/local/share/icecast/web/xml2json.xslt

# Build LiquidSoap

# For libfdk-aac-dev
RUN sed -e 's#main#main contrib non-free#' -i /etc/apt/sources.list

RUN apt-get update && \
    apt-get install -y --no-install-recommends ocaml git rsync opam libpcre3-dev libfdk-aac-dev libmad0-dev \
      libmp3lame-dev libtag1-dev libfaad-dev libflac-dev m4 aspcud

USER azuracast

RUN opam init -y && \
    opam install -y taglib.0.3.3 mad.0.4.5 faad.0.3.3 fdkaac.0.2.1 lame.0.3.3 vorbis.0.6.2 cry.0.4.1 flac.0.1.2 liquidsoap.1.2.1

USER root

EXPOSE 9001
EXPOSE 8000-8500

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]