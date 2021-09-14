export default {
  inject: ['getStream', 'resumeStream'],
  data: function () {
    return {
      'controlsNode': null,

      'trackGain': 0,
      'trackGainObj': null,

      'destination': null,
      'sink': null,

      'passThrough': false,
      'passThroughObj': null,

      'source': null,
      'playing': false,
      'paused': false,
      'position': 0.0,
      'volume': 100,
      'volumeLeft': 0,
      'volumeRight': 0
    };
  },
  mounted: function () {
    this.sink = this.getStream().webcast;
  },
  watch: {
    volume: function (val, oldVal) {
      this.setTrackGain(val);
    }
  },
  methods: {
    createControlsNode: function () {
      var bufferLength,
        bufferLog,
        bufferSize,
        log10,
        source;

      bufferSize = 4096;
      bufferLength = parseFloat(bufferSize) / parseFloat(this.getStream().context.sampleRate);
      bufferLog = Math.log(parseFloat(bufferSize));
      log10 = 2.0 * Math.log(10);

      source = this.getStream().context.createScriptProcessor(bufferSize, 2, 2);

      source.onaudioprocess = (buf) => {
        var channel,
          channelData,
          i,
          j,
          k,
          ref1,
          ref2,
          ref3,
          results,
          ret,
          rms,
          volume;
        ret = {};

        if (((ref1 = this.source) != null ? ref1.position : void 0) != null) {
          this.position = this.source.position();
        } else {
          if (this.source != null) {
            this.position = parseFloat(this.position) + bufferLength;
          }
        }
        results = [];
        for (channel = j = 0, ref2 = buf.inputBuffer.numberOfChannels - 1; (0 <= ref2 ? j <= ref2 : j >= ref2); channel = 0 <= ref2 ? ++j : --j) {
          channelData = buf.inputBuffer.getChannelData(channel);
          rms = 0.0;
          for (i = k = 0, ref3 = channelData.length - 1; (0 <= ref3 ? k <= ref3 : k >= ref3); i = 0 <= ref3 ? ++k : --k) {
            rms += Math.pow(channelData[i], 2);
          }
          volume = 100 * Math.exp((Math.log(rms) - bufferLog) / log10);
          if (channel === 0) {
            this.volumeLeft = volume;
          } else {
            this.volumeRight = volume;
          }

          results.push(buf.outputBuffer.getChannelData(channel).set(channelData));
        }
        return results;
      };
      return source;
    },

    createPassThrough: function () {
      var source;
      source = this.getStream().context.createScriptProcessor(256, 2, 2);
      source.onaudioprocess = (buf) => {
        var channel,
          channelData,
          j,
          ref1,
          results;
        channelData = buf.inputBuffer.getChannelData(channel);
        results = [];
        for (channel = j = 0, ref1 = buf.inputBuffer.numberOfChannels - 1; (0 <= ref1 ? j <= ref1 : j >= ref1); channel = 0 <= ref1 ? ++j : --j) {
          if (this.passThrough) {
            results.push(buf.outputBuffer.getChannelData(channel).set(channelData));
          } else {
            results.push(buf.outputBuffer.getChannelData(channel).set(new Float32Array(channelData.length)));
          }
        }
        return results;
      };
      return source;
    },

    setTrackGain: function (new_gain) {
      return this.trackGainObj.gain.value = parseFloat(new_gain) / 100.0;
    },

    togglePause: function () {
      var ref1,
        ref2;
      if (((ref1 = this.source) != null ? ref1.pause : void 0) == null) {
        return;
      }
      if ((ref2 = this.source) != null ? typeof ref2.paused === 'function' ? ref2.paused() : void 0 : void 0) {
        this.source.play();
        this.playing = true;
        this.paused = false;
      } else {
        this.source.pause();
        this.playing = false;
        this.paused = true;
      }
    },

    prepare: function () {
      this.controlsNode = this.createControlsNode();
      this.controlsNode.connect(this.sink);

      this.trackGainObj = this.getStream().context.createGain();
      this.trackGainObj.connect(this.controlsNode);
      this.trackGainObj.gain.value = 1.0;

      this.destination = this.trackGainObj;

      this.passThroughObj = this.createPassThrough();
      this.passThroughObj.connect(this.getStream().context.destination);

      return this.trackGainObj.connect(this.passThroughObj);
    },

    stop: function () {
      var ref1,
        ref2,
        ref3,
        ref4,
        ref5;
      if ((ref1 = this.source) != null) {
        if (typeof ref1.stop === 'function') {
          ref1.stop();
        }
      }
      if ((ref2 = this.source) != null) {
        ref2.disconnect();
      }
      if ((ref3 = this.trackGainObj) != null) {
        ref3.disconnect();
      }
      if ((ref4 = this.controlsNode) != null) {
        ref4.disconnect();
      }
      if ((ref5 = this.passThroughObj) != null) {
        ref5.disconnect();
      }
      this.source = this.trackGainObj = this.controlsNode = this.passThroughObj = null;

      this.position = 0.0;
      this.volumeLeft = 0;
      this.volumeRight = 0;

      this.playing = false;
      this.paused = false;
    },

    seek: function (percent) {
      var position,
        ref1;
      if (!(position = (ref1 = this.source) != null ? typeof ref1.seek === 'function' ? ref1.seek(percent) : void 0 : void 0)) {
        return;
      }

      this.position = position;
    },

    prettifyTime: function (time) {
      var hours,
        minutes,
        result,
        seconds;
      hours = parseInt(time / 3600);
      time %= 3600;
      minutes = parseInt(time / 60);
      seconds = parseInt(time % 60);
      if (minutes < 10) {
        minutes = `0${minutes}`;
      }
      if (seconds < 10) {
        seconds = `0${seconds}`;
      }
      result = `${minutes}:${seconds}`;
      if (hours > 0) {
        result = `${hours}:${result}`;
      }
      return result;
    },

    sendMetadata: function (file) {
      this.getStream().webcast.sendMetadata(file.metadata);
    }
  }
};
