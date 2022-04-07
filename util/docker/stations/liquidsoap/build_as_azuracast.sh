#!/bin/bash
set -e
set -x

opam init --disable-sandboxing -a --bare && opam switch create 4.13.1

# Pin specific commit of Liquidsoap
opam pin add --no-action liquidsoap https://github.com/savonet/liquidsoap.git#8d94c2bd43390a727edede05b96873e4aa7310ce

opam pin add --no-action mm https://github.com/savonet/ocaml-mm.git#bfff160ece1676a3a912e8bc79c80ce6482f4d36

opam install -y ladspa.0.2.2 ffmpeg.1.1.3 ffmpeg-avutil.1.1.3 ffmpeg-avcodec.1.1.3 ffmpeg-avdevice.1.1.3 \
    ffmpeg-av.1.1.3 ffmpeg-avfilter.1.1.3 ffmpeg-swresample.1.1.3 ffmpeg-swscale.1.1.3 frei0r.0.1.2 \
    samplerate.0.1.6 taglib.0.3.9 mad.0.5.2 faad.0.5.0 fdkaac.0.3.2 lame.0.3.5 vorbis.0.8.0 cry.0.6.6 \
    flac.0.3.0 opus.0.2.1 dtools.0.4.4 duppy.0.9.2 ocurl.0.9.2 ssl.0.5.10 \
    liquidsoap

# Have Liquidsoap build its own chroot.
mkdir -p /tmp/liquidsoap

/var/azuracast/.opam/4.13.1/bin/liquidsoap /bd_build/stations/liquidsoap/build_chroot.liq || true

# Clear entire OPAM directory
rm -rf /var/azuracast/.opam

cp -r /tmp/liquidsoap/var/azuracast/.opam /var/azuracast/.opam
rm -rf /tmp/liquidsoap

