(function() {
  var base, base1;

  navigator.mediaDevices || (navigator.mediaDevices = {});

  (base = navigator.mediaDevices).getUserMedia || (base.getUserMedia = function(constraints) {
    var fn;
    fn = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
    if (fn == null) {
      return Promise.reject(new Error("getUserMedia is not implemented in this browser"));
    }
    return new Promise(function(resolve, reject) {
      return fn.call(navigator, constraints, resolve, reject);
    });
  });

  (base1 = navigator.mediaDevices).enumerateDevices || (base1.enumerateDevices = function() {
    return Promise.reject(new Error("enumerateDevices is not implemented on this browser"));
  });

}).call(this);

(function() {
  var Webcaster;

  window.Webcaster = Webcaster = {
    View: {},
    Model: {},
    Source: {},
    prettifyTime: function(time) {
      var hours, minutes, result, seconds;
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
    }
  };

}).call(this);

(function() {
  Webcaster.Node = (function() {
    var defaultChannels;

    class Node {
      constructor({
          model: model1
        }) {
        this.model = model1;
        if (typeof webkitAudioContext !== "undefined") {
          this.context = new webkitAudioContext;
        } else {
          this.context = new AudioContext;
        }
        this.webcast = this.context.createWebcastSource(4096, defaultChannels);
        this.connect();
        this.model.on("change:passThrough", () => {
          return this.webcast.setPassThrough(this.model.get("passThrough"));
        });
        this.model.on("change:channels", () => {
          return this.reconnect();
        });
      }

      connect() {
        if (this.model.get("channels") === 1) {
          this.merger || (this.merger = this.context.createChannelMerger(this.defaultChannels));
          this.merger.connect(this.context.destination);
          return this.webcast.connect(this.merger);
        } else {
          return this.webcast.connect(this.context.destination);
        }
      }

      disconnect() {
        var ref;
        this.webcast.disconnect();
        return (ref = this.merger) != null ? ref.disconnect() : void 0;
      }

      reconnect() {
        this.disconnect();
        return this.connect();
      }

      startStream() {
        var encoder;
        switch (this.model.get("encoder")) {
          case "mp3":
            encoder = Webcast.Encoder.Mp3;
            break;
          case "raw":
            encoder = Webcast.Encoder.Raw;
        }
        this.encoder = new encoder({
          channels: this.model.get("channels"),
          samplerate: this.model.get("samplerate"),
          bitrate: this.model.get("bitrate")
        });
        if (this.model.get("samplerate") !== this.context.sampleRate) {
          this.encoder = new Webcast.Encoder.Resample({
            encoder: this.encoder,
            type: Samplerate.LINEAR,
            samplerate: this.context.sampleRate
          });
        }
        if (this.model.get("asynchronous")) {
          this.encoder = new Webcast.Encoder.Asynchronous({
            encoder: this.encoder,
            scripts: ["https://cdn.rawgit.com/webcast/libsamplerate.js/master/dist/libsamplerate.js", "https://cdn.rawgit.com/savonet/shine/master/js/dist/libshine.js", "https://cdn.rawgit.com/webcast/webcast.js/master/lib/webcast.js"]
          });
        }
        return this.webcast.connectSocket(this.encoder, this.model.get("uri"));
      }

      stopStream() {
        return this.webcast.close();
      }

      createAudioSource({file, audio}, model, cb) {
        var el, source;
        el = new Audio(URL.createObjectURL(file));
        el.controls = false;
        el.autoplay = false;
        el.loop = false;
        el.addEventListener("ended", () => {
          return model.onEnd();
        });
        source = null;
        return el.addEventListener("canplay", () => {
          if (source != null) {
            return;
          }
          source = this.context.createMediaElementSource(el);
          source.play = function() {
            return el.play();
          };
          source.position = function() {
            return el.currentTime;
          };
          source.duration = function() {
            return el.duration;
          };
          source.paused = function() {
            return el.paused;
          };
          source.stop = function() {
            el.pause();
            return el.remove();
          };
          source.pause = function() {
            return el.pause();
          };
          source.seek = function(percent) {
            var time;
            time = percent * parseFloat(audio.length);
            el.currentTime = time;
            return time;
          };
          return cb(source);
        });
      }

      createFileSource(file, model, cb) {
        var ref;
        if ((ref = this.source) != null) {
          ref.disconnect();
        }
        return this.createAudioSource(file, model, cb);
      }

      createMicrophoneSource(constraints, cb) {
        return navigator.mediaDevices.getUserMedia(constraints).then((stream) => {
          var source;
          source = this.context.createMediaStreamSource(stream);
          source.stop = function() {
            var ref;
            return (ref = stream.getAudioTracks()) != null ? ref[0].stop() : void 0;
          };
          return cb(source);
        });
      }

      sendMetadata(data) {
        return this.webcast.sendMetadata(data);
      }

      close(cb) {
        return this.webcast.close(cb);
      }

    };

    _.extend(Node.prototype, Backbone.Events);

    defaultChannels = 2;

    return Node;

  }).call(this);

}).call(this);

(function() {
  var ref,
    boundMethodCheck = function(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new Error('Bound instance method accessed before binding'); } };

  ref = Webcaster.Model.Track = class Track extends Backbone.Model {
    constructor() {
      super(...arguments);
      this.setTrackGain = this.setTrackGain.bind(this);
    }

    initialize(attributes, options) {
      this.node = options.node;
      this.mixer = options.mixer;
      this.mixer.on("cue", () => {
        return this.set({
          passThrough: false
        });
      });
      this.on("change:trackGain", this.setTrackGain);
      this.on("ended", this.stop);
      return this.sink = this.node.webcast;
    }

    togglePassThrough() {
      var passThrough;
      passThrough = this.get("passThrough");
      if (passThrough) {
        return this.set({
          passThrough: false
        });
      } else {
        this.mixer.trigger("cue");
        return this.set({
          passThrough: true
        });
      }
    }

    isPlaying() {
      return this.source != null;
    }

    createControlsNode() {
      var bufferLength, bufferLog, bufferSize, log10, source;
      bufferSize = 4096;
      bufferLength = parseFloat(bufferSize) / parseFloat(this.node.context.sampleRate);
      bufferLog = Math.log(parseFloat(bufferSize));
      log10 = 2.0 * Math.log(10);
      source = this.node.context.createScriptProcessor(bufferSize, 2, 2);
      source.onaudioprocess = (buf) => {
        var channel, channelData, i, j, k, ref1, ref2, ref3, results, ret, rms, volume;
        ret = {};
        if (((ref1 = this.source) != null ? ref1.position : void 0) != null) {
          ret["position"] = this.source.position();
        } else {
          if (this.source != null) {
            ret["position"] = parseFloat(this.get("position")) + bufferLength;
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
            ret["volumeLeft"] = volume;
          } else {
            ret["volumeRight"] = volume;
          }
          this.set(ret);
          results.push(buf.outputBuffer.getChannelData(channel).set(channelData));
        }
        return results;
      };
      return source;
    }

    createPassThrough() {
      var source;
      source = this.node.context.createScriptProcessor(256, 2, 2);
      source.onaudioprocess = (buf) => {
        var channel, channelData, j, ref1, results;
        channelData = buf.inputBuffer.getChannelData(channel);
        results = [];
        for (channel = j = 0, ref1 = buf.inputBuffer.numberOfChannels - 1; (0 <= ref1 ? j <= ref1 : j >= ref1); channel = 0 <= ref1 ? ++j : --j) {
          if (this.get("passThrough")) {
            results.push(buf.outputBuffer.getChannelData(channel).set(channelData));
          } else {
            results.push(buf.outputBuffer.getChannelData(channel).set(new Float32Array(channelData.length)));
          }
        }
        return results;
      };
      return source;
    }

    setTrackGain() {
      boundMethodCheck(this, ref);
      if (this.trackGain == null) {
        return;
      }
      return this.trackGain.gain.value = parseFloat(this.get("trackGain")) / 100.0;
    }

    prepare() {
      this.controlsNode = this.createControlsNode();
      this.controlsNode.connect(this.sink);
      this.trackGain = this.node.context.createGain();
      this.trackGain.connect(this.controlsNode);
      this.setTrackGain();
      this.destination = this.trackGain;
      this.passThrough = this.createPassThrough();
      this.passThrough.connect(this.node.context.destination);
      return this.destination.connect(this.passThrough);
    }

    togglePause() {
      var ref1, ref2;
      if (((ref1 = this.source) != null ? ref1.pause : void 0) == null) {
        return;
      }
      if ((ref2 = this.source) != null ? typeof ref2.paused === "function" ? ref2.paused() : void 0 : void 0) {
        this.source.play();
        return this.trigger("playing");
      } else {
        this.source.pause();
        return this.trigger("paused");
      }
    }

    stop() {
      var ref1, ref2, ref3, ref4, ref5;
      if ((ref1 = this.source) != null) {
        if (typeof ref1.stop === "function") {
          ref1.stop();
        }
      }
      if ((ref2 = this.source) != null) {
        ref2.disconnect();
      }
      if ((ref3 = this.trackGain) != null) {
        ref3.disconnect();
      }
      if ((ref4 = this.controlsNode) != null) {
        ref4.disconnect();
      }
      if ((ref5 = this.passThrough) != null) {
        ref5.disconnect();
      }
      this.source = this.trackGain = this.controlsNode = this.passThrough = null;
      this.set({
        position: 0.0
      });
      return this.trigger("stopped");
    }

    seek(percent) {
      var position, ref1;
      if (!(position = (ref1 = this.source) != null ? typeof ref1.seek === "function" ? ref1.seek(percent) : void 0 : void 0)) {
        return;
      }
      return this.set({
        position: position
      });
    }

    sendMetadata(file) {
      return this.node.sendMetadata(file.metadata);
    }

  };

}).call(this);

(function() {
  Webcaster.Model.Microphone = class Microphone extends Webcaster.Model.Track {
    initialize(attributes, options) {
      super.initialize(attributes, options);
      return this.on("change:device", function() {
        if (this.source == null) {
          return;
        }
        return this.createSource();
      });
    }

    createSource(cb) {
      var constraints;
      if (this.source != null) {
        this.source.disconnect(this.destination);
      }
      constraints = {
        video: false
      };
      if (this.get("device")) {
        constraints.audio = {
          exact: this.get("device")
        };
      } else {
        constraints.audio = true;
      }
      return this.node.createMicrophoneSource(constraints, (source) => {
        this.source = source;
        this.source.connect(this.destination);
        return typeof cb === "function" ? cb() : void 0;
      });
    }

    play() {
      this.prepare();
      return this.createSource(() => {
        return this.trigger("playing");
      });
    }

  };

}).call(this);

(function() {
  Webcaster.Model.Mixer = class Mixer extends Backbone.Model {
    getVolume(position) {
      if (position < 0.5) {
        return 2 * position;
      }
      return 1;
    }

    getSlider() {
      return parseFloat(this.get("slider")) / 100.00;
    }

    getLeftVolume() {
      return this.getVolume(1.0 - this.getSlider());
    }

    getRightVolume() {
      return this.getVolume(this.getSlider());
    }

  };

}).call(this);

(function() {
  var ref,
    boundMethodCheck = function(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new Error('Bound instance method accessed before binding'); } };

  ref = Webcaster.Model.Playlist = class Playlist extends Webcaster.Model.Track {
    constructor() {
      super(...arguments);
      this.setMixGain = this.setMixGain.bind(this);
    }

    initialize(attributes, options) {
      super.initialize(attributes, options);
      this.mixer.on("change:slider", this.setMixGain);
      this.mixGain = this.node.context.createGain();
      this.mixGain.connect(this.node.webcast);
      return this.sink = this.mixGain;
    }

    setMixGain() {
      boundMethodCheck(this, ref);
      if (this.mixGain == null) {
        return;
      }
      if (this.get("side") === "left") {
        return this.mixGain.gain.value = this.mixer.getLeftVolume();
      } else {
        return this.mixGain.gain.value = this.mixer.getRightVolume();
      }
    }

    appendFiles(newFiles, cb) {
      var addFile, files, i, j, onDone, ref1, results;
      files = this.get("files");
      onDone = _.after(newFiles.length, () => {
        this.set({
          files: files
        });
        return typeof cb === "function" ? cb() : void 0;
      });
      addFile = function(file) {
        return file.readTaglibMetadata((data) => {
          files.push({
            file: file,
            audio: data.audio,
            metadata: data.metadata
          });
          return onDone();
        });
      };
      results = [];
      for (i = j = 0, ref1 = newFiles.length - 1; (0 <= ref1 ? j <= ref1 : j >= ref1); i = 0 <= ref1 ? ++j : --j) {
        results.push(addFile(newFiles[i]));
      }
      return results;
    }

    selectFile(options = {}) {
      var file, files, index;
      files = this.get("files");
      index = this.get("fileIndex");
      if (files.length === 0) {
        return;
      }
      index += options.backward ? -1 : 1;
      if (index < 0) {
        index = files.length - 1;
      }
      if (index >= files.length) {
        if (!this.get("loop")) {
          this.set({
            fileIndex: -1
          });
          return;
        }
        if (index < 0) {
          index = files.length - 1;
        } else {
          index = 0;
        }
      }
      file = files[index];
      this.set({
        fileIndex: index
      });
      return file;
    }

    play(file) {
      this.prepare();
      this.setMixGain();
      return this.node.createFileSource(file, this, (source) => {
        var ref1;
        this.source = source;
        this.source.connect(this.destination);
        if (this.source.duration != null) {
          this.set({
            duration: this.source.duration()
          });
        } else {
          if (((ref1 = file.audio) != null ? ref1.length : void 0) != null) {
            this.set({
              duration: parseFloat(file.audio.length)
            });
          }
        }
        this.source.play(file);
        return this.trigger("playing");
      });
    }

    onEnd() {
      this.stop();
      if (this.get("playThrough")) {
        return this.play(this.selectFile());
      }
    }

  };

}).call(this);

(function() {
  Webcaster.Model.Settings = class Settings extends Backbone.Model {
    initialize(attributes, options) {
      this.mixer = options.mixer;
      return this.mixer.on("cue", () => {
        return this.set({
          passThrough: false
        });
      });
    }

    togglePassThrough() {
      var passThrough;
      passThrough = this.get("passThrough");
      if (passThrough) {
        return this.set({
          passThrough: false
        });
      } else {
        this.mixer.trigger("cue");
        return this.set({
          passThrough: true
        });
      }
    }

    rebuildUri() {
      return this.set({
        uri: "wss://" + this.get("dj_username") + ":" + this.get("dj_password") + "@" + this.get("base_uri")
      });
    }

  };

}).call(this);

(function() {
  Webcaster.View.Track = class Track extends Backbone.View {
    initialize() {
      this.model.on("change:passThrough", () => {
        if (this.model.get("passThrough")) {
          return this.$(".passThrough").addClass("btn-cued").removeClass("btn-info");
        } else {
          return this.$(".passThrough").addClass("btn-info").removeClass("btn-cued");
        }
      });
      this.model.on("change:volumeLeft", () => {
        return this.$(".volume-left").width(`${this.model.get("volumeLeft")}%`);
      });
      return this.model.on("change:volumeRight", () => {
        return this.$(".volume-right").width(`${this.model.get("volumeRight")}%`);
      });
    }

    onPassThrough(e) {
      e.preventDefault();
      return this.model.togglePassThrough();
    }

    onSubmit(e) {
      return e.preventDefault();
    }

  };

}).call(this);

(function() {
  Webcaster.View.Microphone = (function() {
    class Microphone extends Webcaster.View.Track {
      initialize() {
        super.initialize();
        this.model.on("playing", () => {
          this.$(".play-control").removeAttr("disabled");
          this.$(".record-audio").addClass("btn-recording");
          this.$(".volume-left").width("0%");
          return this.$(".volume-right").width("0%");
        });
        return this.model.on("stopped", () => {
          this.$(".record-audio").removeClass("btn-recording");
          this.$(".volume-left").width("0%");
          return this.$(".volume-right").width("0%");
        });
      }

      render() {
        this.$(".microphone-slider").slider({
          orientation: "vertical",
          min: 0,
          max: 150,
          value: 100,
          stop: () => {
            return this.$("a.ui-slider-handle").tooltip("hide");
          },
          slide: (e, ui) => {
            this.model.set({
              trackGain: ui.value
            });
            return this.$("a.ui-slider-handle").tooltip("show");
          }
        });
        this.$("a.ui-slider-handle").tooltip({
          title: () => {
            return this.model.get("trackGain");
          },
          trigger: "",
          animation: false,
          placement: "left"
        });
        navigator.mediaDevices.getUserMedia({
          audio: true,
          video: false
        }).then(() => {
          return navigator.mediaDevices.enumerateDevices().then((devices) => {
            var $select;
            devices = _.filter(devices, function({kind, deviceId}) {
              return kind === "audioinput";
            });
            if (_.isEmpty(devices)) {
              return;
            }
            $select = this.$(".microphone-entry select");
            _.each(devices, function({label, deviceId}) {
              return $select.append(`<option value='${deviceId}'>${label}</option>`);
            });
            $select.find("option:eq(0)").prop("selected", true);
            this.model.set("device", $select.val());
            $select.select(function() {
              return this.model.set("device", $select.val());
            });
            return this.$(".microphone-entry").show();
          });
        });
        return this;
      }

      onRecord(e) {
        e.preventDefault();
        if (this.model.isPlaying()) {
          return this.model.stop();
        }
        this.$(".play-control").attr({
          disabled: "disabled"
        });
        return this.model.play();
      }

    };

    Microphone.prototype.events = {
      "click .record-audio": "onRecord",
      "click .passThrough": "onPassThrough",
      "submit": "onSubmit"
    };

    return Microphone;

  }).call(this);

}).call(this);

(function() {
  Webcaster.View.Mixer = (function() {
    class Mixer extends Backbone.View {
      onMixerPositionChange(e) {
        return this.model.set({
          slider: $(e.target).val()
        });
      }

    };

    Mixer.prototype.events = {
      "change .slider": "onMixerPositionChange"
    };

    return Mixer;

  }).call(this);

}).call(this);

(function() {
  Webcaster.View.Playlist = (function() {
    class Playlist extends Webcaster.View.Track {
      initialize() {
        super.initialize();
        this.model.on("change:fileIndex", () => {
          this.$(".track-row").removeClass("success");
          return this.$(`.track-row-${this.model.get("fileIndex")}`).addClass("success");
        });
        this.model.on("playing", () => {
          this.$(".play-control").removeAttr("disabled");
          this.$(".play-audio").hide();
          this.$(".pause-audio").show();
          this.$(".track-position-text").removeClass("blink").text("");
          this.$(".volume-left").width("0%");
          this.$(".volume-right").width("0%");
          if (this.model.get("duration")) {
            return this.$(".progress-volume").css("cursor", "pointer");
          } else {
            this.$(".track-position").addClass("progress-striped active");
            return this.setTrackProgress(100);
          }
        });
        this.model.on("paused", () => {
          this.$(".play-audio").show();
          this.$(".pause-audio").hide();
          this.$(".volume-left").width("0%");
          this.$(".volume-right").width("0%");
          return this.$(".track-position-text").addClass("blink");
        });
        this.model.on("stopped", () => {
          this.$(".play-audio").show();
          this.$(".pause-audio").hide();
          this.$(".progress-volume").css("cursor", "");
          this.$(".track-position").removeClass("progress-striped active");
          this.setTrackProgress(0);
          this.$(".track-position-text").removeClass("blink").text("");
          this.$(".volume-left").width("0%");
          return this.$(".volume-right").width("0%");
        });
        return this.model.on("change:position", () => {
          var duration, position;
          if (!(duration = this.model.get("duration"))) {
            return;
          }
          position = parseFloat(this.model.get("position"));
          this.setTrackProgress(100.0 * position / parseFloat(duration));
          return this.$(".track-position-text").text(`${Webcaster.prettifyTime(position)} / ${Webcaster.prettifyTime(duration)}`);
        });
      }

      render() {
        var files;
        files = this.model.get("files");
        this.$(".files-table").empty();
        if (!(files.length > 0)) {
          return this;
        }
        _.each(files, ({file, audio, metadata}, index) => {
          var klass, time;
          if ((audio != null ? audio.length : void 0) !== 0) {
            time = Webcaster.prettifyTime(audio.length);
          } else {
            time = "N/A";
          }
          if (this.model.get("fileIndex") === index) {
            klass = "success";
          } else {
            klass = "";
          }
          return this.$(".files-table").append(`<tr class='track-row track-row-${index} ${klass}'>\n  <td>${index + 1}</td>\n  <td>${(metadata != null ? metadata.title : void 0) || "Unknown Title"}</td>\n  <td>${(metadata != null ? metadata.artist : void 0) || "Unknown Artist"}</td>\n  <td>${time}</td>\n</tr>`);
        });
        this.$(".playlist-table").show();
        return this;
      }

      setTrackProgress(percent) {
        this.$(".track-position").width(`${percent * $(".progress-volume").width() / 100}px`);
        return this.$(".track-position-text,.progress-seek").width($(".progress-volume").width());
      }

      play(options) {
        this.model.stop();
        if (!(this.file = this.model.selectFile(options))) {
          return;
        }
        this.$(".play-control").attr({
          disabled: "disabled"
        });
        return this.model.play(this.file);
      }

      onPlay(e) {
        e.preventDefault();
        if (this.model.isPlaying()) {
          this.model.togglePause();
          return;
        }
        return this.play();
      }

      onPause(e) {
        e.preventDefault();
        return this.model.togglePause();
      }

      onPrevious(e) {
        e.preventDefault();
        if (this.model.isPlaying() == null) {
          return;
        }
        return this.play({
          backward: true
        });
      }

      onNext(e) {
        e.preventDefault();
        if (!this.model.isPlaying()) {
          return;
        }
        return this.play();
      }

      onStop(e) {
        e.preventDefault();
        this.$(".track-row").removeClass("success");
        this.model.stop();
        return this.file = null;
      }

      onSeek(e) {
        e.preventDefault();
        return this.model.seek((e.pageX - $(e.target).offset().left) / $(e.target).width());
      }

      onFiles() {
        var files;
        files = this.$(".files")[0].files;
        this.$(".files").attr({
          disabled: "disabled"
        });
        return this.model.appendFiles(files, () => {
          this.$(".files").removeAttr("disabled").val("");
          return this.render();
        });
      }

      onPlayThrough(e) {
        return this.model.set({
          playThrough: $(e.target).is(":checked")
        });
      }

      onLoop(e) {
        return this.model.set({
          loop: $(e.target).is(":checked")
        });
      }

      onVolumeChange(e) {
        return this.model.set({
          trackGain: $(e.target).val()
        });
      }

    };

    Playlist.prototype.events = {
      "click .play-audio": "onPlay",
      "click .pause-audio": "onPause",
      "click .previous": "onPrevious",
      "click .next": "onNext",
      "click .stop": "onStop",
      "click .progress-seek": "onSeek",
      "click .passThrough": "onPassThrough",
      "change .files": "onFiles",
      "change .playThrough": "onPlayThrough",
      "change .loop": "onLoop",
      "change .volume-slider": "onVolumeChange",
      "submit": "onSubmit"
    };

    return Playlist;

  }).call(this);

}).call(this);

(function() {
  Webcaster.View.Settings = (function() {
    class Settings extends Backbone.View {
      initialize({node}) {
        this.node = node;
        return this.model.on("change:passThrough", () => {
          if (this.model.get("passThrough")) {
            return this.$(".passThrough").addClass("btn-cued").removeClass("btn-info");
          } else {
            return this.$(".passThrough").addClass("btn-info").removeClass("btn-cued");
          }
        });
      }

      render() {
        var bitrate, samplerate;
        samplerate = this.model.get("samplerate");
        this.$(".samplerate").empty();
        _.each(this.model.get("samplerates"), (rate) => {
          var selected;
          selected = samplerate === rate ? "selected" : "";
          return $(`<option value='${rate}' ${selected}>${rate}</option>`).appendTo(this.$(".samplerate"));
        });
        bitrate = this.model.get("bitrate");
        this.$(".bitrate").empty();
        _.each(this.model.get("bitrates"), (rate) => {
          var selected;
          selected = bitrate === rate ? "selected" : "";
          return $(`<option value='${rate}' ${selected}>${rate}</option>`).appendTo(this.$(".bitrate"));
        });
        return this;
      }

      onUri() {
        return this.model.set({
          uri: this.$(".uri").val()
        });
      }

      onEncoder(e) {
        return this.model.set({
          encoder: $(e.target).val()
        });
      }

      onChannels(e) {
        return this.model.set({
          channels: parseInt($(e.target).val())
        });
      }

      onSamplerate(e) {
        return this.model.set({
          samplerate: parseInt($(e.target).val())
        });
      }

      onBitrate(e) {
        return this.model.set({
          bitrate: parseInt($(e.target).val())
        });
      }

      onAsynchronous(e) {
        return this.model.set({
          asynchronous: $(e.target).is(":checked")
        });
      }

      onPassThrough(e) {
        e.preventDefault();
        return this.model.togglePassThrough();
      }

      onStart(e) {
        e.preventDefault();
        this.$(".stop-stream").show();
        this.$(".start-stream").hide();
        this.$("input, select").attr({
          disabled: "disabled"
        });
        this.$(".manual-metadata, .update-metadata").removeAttr("disabled");
        return this.node.startStream();
      }

      onStop(e) {
        e.preventDefault();
        this.$(".stop-stream").hide();
        this.$(".start-stream").show();
        this.$("input, select").removeAttr("disabled");
        this.$(".manual-metadata, .update-metadata").attr({
          disabled: "disabled"
        });
        return this.node.stopStream();
      }

      onMetadataUpdate(e) {
        var artist, title;
        e.preventDefault();
        title = this.$(".manual-metadata.artist").val();
        artist = this.$(".manual-metadata.title").val();
        if (!(artist !== "" || title !== "")) {
          return;
        }
        this.node.sendMetadata({
          artist: artist,
          title: title
        });
        return this.$(".metadata-updated").show(400, () => {
          var cb;
          cb = () => {
            return this.$(".metadata-updated").hide(400);
          };
          return setTimeout(cb, 2000);
        });
      }

      onSubmit(e) {
        return e.preventDefault();
      }

      onDjCredentialsChange(e) {
        this.model.set({
          dj_username: this.$(".dj_username").val()
        });
        this.model.set({
          dj_password: this.$(".dj_password").val()
        });
        return this.model.rebuildUri();
      }

    };

    Settings.prototype.events = {
      "change .uri": "onUri",
      "change input.encoder": "onEncoder",
      "change input.channels": "onChannels",
      "change .samplerate": "onSamplerate",
      "change .bitrate": "onBitrate",
      "change .asynchronous": "onAsynchronous",
      "change .dj_username": "onDjCredentialsChange",
      "change .dj_password": "onDjCredentialsChange",
      "click .passThrough": "onPassThrough",
      "click .start-stream": "onStart",
      "click .stop-stream": "onStop",
      "click .update-metadata": "onMetadataUpdate",
      "submit": "onSubmit"
    };

    return Settings;

  }).call(this);

}).call(this);
