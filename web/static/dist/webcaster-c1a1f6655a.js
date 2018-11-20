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
    initialize() {
      super.initialize();
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

    initialize() {
      super.initialize();
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
  Webcaster.View.Mixer = class Mixer extends Backbone.View {
    render() {
      this.$(".slider").slider({
        stop: () => {
          return this.$("a.ui-slider-handle").tooltip("hide");
        },
        slide: (e, ui) => {
          this.model.set({
            slider: ui.value
          });
          return this.$("a.ui-slider-handle").tooltip("show");
        }
      });
      this.$("a.ui-slider-handle").tooltip({
        title: () => {
          return this.model.get("slider");
        },
        trigger: "",
        animation: false,
        placement: "bottom"
      });
      return this;
    }

  };

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
        this.$(".volume-slider").slider({
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

    };

    Settings.prototype.events = {
      "change .uri": "onUri",
      "change input.encoder": "onEncoder",
      "change input.channels": "onChannels",
      "change .samplerate": "onSamplerate",
      "change .bitrate": "onBitrate",
      "change .asynchronous": "onAsynchronous",
      "click .passThrough": "onPassThrough",
      "click .start-stream": "onStart",
      "click .stop-stream": "onStop",
      "click .update-metadata": "onMetadataUpdate",
      "submit": "onSubmit"
    };

    return Settings;

  }).call(this);

}).call(this);

(function() {
  $(function() {
    Webcaster.mixer = new Webcaster.Model.Mixer({
      slider: 0
    });
    Webcaster.settings = new Webcaster.Model.Settings({
      uri: "ws://source:hackme@localhost:8080/mount",
      bitrate: 128,
      bitrates: [8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 192, 224, 256, 320],
      samplerate: 44100,
      samplerates: [8000, 11025, 12000, 16000, 22050, 24000, 32000, 44100, 48000],
      channels: 2,
      encoder: "mp3",
      asynchronous: true,
      passThrough: false
    }, {
      mixer: Webcaster.mixer
    });
    Webcaster.node = new Webcaster.Node({
      model: Webcaster.settings
    });
    _.extend(Webcaster, {
      views: {
        settings: new Webcaster.View.Settings({
          model: Webcaster.settings,
          node: Webcaster.node,
          el: $("div.settings")
        }),
        mixer: new Webcaster.View.Mixer({
          model: Webcaster.mixer,
          el: $("div.mixer")
        }),
        microphone: new Webcaster.View.Microphone({
          model: new Webcaster.Model.Microphone({
            trackGain: 100,
            passThrough: false
          }, {
            mixer: Webcaster.mixer,
            node: Webcaster.node
          }),
          el: $("div.microphone")
        }),
        playlistLeft: new Webcaster.View.Playlist({
          model: new Webcaster.Model.Playlist({
            side: "left",
            files: [],
            fileIndex: -1,
            volumeLeft: 0,
            volumeRight: 0,
            trackGain: 100,
            passThrough: false,
            playThrough: true,
            position: 0.0,
            loop: false
          }, {
            mixer: Webcaster.mixer,
            node: Webcaster.node
          }),
          el: $("div.playlist-left")
        }),
        playlistRight: new Webcaster.View.Playlist({
          model: new Webcaster.Model.Playlist({
            side: "right",
            files: [],
            fileIndex: -1,
            volumeLeft: 0,
            volumeRight: 0,
            trackGain: 100,
            passThrough: false,
            playThrough: true,
            position: 0.0,
            loop: false
          }, {
            mixer: Webcaster.mixer,
            node: Webcaster.node
          }),
          el: $("div.playlist-right")
        })
      }
    });
    return _.invoke(Webcaster.views, "render");
  });

}).call(this);

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImNvbXBhdC5jb2ZmZWUiLCJ3ZWJjYXN0ZXIuY29mZmVlIiwibm9kZS5jb2ZmZWUiLCJ0cmFjay5jb2ZmZWUiLCJtaWNyb3Bob25lLmNvZmZlZSIsIm1peGVyLmNvZmZlZSIsInBsYXlsaXN0LmNvZmZlZSIsInNldHRpbmdzLmNvZmZlZSIsImluaXQuY29mZmVlIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBO0FBQUEsTUFBQSxJQUFBLEVBQUE7O0VBQUEsU0FBUyxDQUFDLGlCQUFWLFNBQVMsQ0FBQyxlQUFpQixDQUFBOztVQUUzQixTQUFTLENBQUMsYUFBWSxDQUFDLHFCQUFELENBQUMsZUFBaUIsUUFBQSxDQUFDLFdBQUQsQ0FBQTtBQUN0QyxRQUFBO0lBQUEsRUFBQSxHQUFLLFNBQVMsQ0FBQyxZQUFWLElBQTBCLFNBQVMsQ0FBQyxrQkFBcEMsSUFBMEQsU0FBUyxDQUFDLGVBQXBFLElBQXVGLFNBQVMsQ0FBQztJQUV0RyxJQUFPLFVBQVA7QUFDRSxhQUFPLE9BQU8sQ0FBQyxNQUFSLENBQWUsSUFBSSxLQUFKLENBQVUsaURBQVYsQ0FBZixFQURUOztXQUdBLElBQUksT0FBSixDQUFZLFFBQUEsQ0FBQyxPQUFELEVBQVUsTUFBVixDQUFBO2FBQ1YsRUFBRSxDQUFDLElBQUgsQ0FBUSxTQUFSLEVBQW1CLFdBQW5CLEVBQWdDLE9BQWhDLEVBQXlDLE1BQXpDO0lBRFUsQ0FBWjtFQU5zQzs7V0FTeEMsU0FBUyxDQUFDLGFBQVksQ0FBQywwQkFBRCxDQUFDLG1CQUFxQixRQUFBLENBQUEsQ0FBQTtXQUMxQyxPQUFPLENBQUMsTUFBUixDQUFlLElBQUksS0FBSixDQUFVLHFEQUFWLENBQWY7RUFEMEM7QUFYNUM7OztBQ0FBO0FBQUEsTUFBQTs7RUFBQSxNQUFNLENBQUMsU0FBUCxHQUFtQixTQUFBLEdBQ2pCO0lBQUEsSUFBQSxFQUFNLENBQUEsQ0FBTjtJQUNBLEtBQUEsRUFBTyxDQUFBLENBRFA7SUFFQSxNQUFBLEVBQVEsQ0FBQSxDQUZSO0lBSUEs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LENBQUMsUUFBcEIsQ0FBNkI7WUFDbkMsSUFBQSxFQUFjLE9BRHFCO1lBRW5DLEtBQUEsRUFBYyxFQUZxQjtZQUduQyxTQUFBLEVBQWMsQ0FBQyxDQUhvQjtZQUluQyxVQUFBLEVBQWMsQ0FKcUI7WUFLbkMsV0FBQSxFQUFjLENBTHFCO1lBTW5DLFNBQUEsRUFBYyxHQU5xQjtZQU9uQyxXQUFBLEVBQWMsS0FQcUI7WUFRbkMsV0FBQSxFQUFjLElBUnFCO1lBU25DLFFBQUEsRUFBYyxHQVRxQjtZQVVuQyxJQUFBLEVBQWM7VUFWcUIsQ0FBN0IsRUFXTDtZQUNELEtBQUEsRUFBUSxTQUFTLENBQUMsS0FEakI7WUFFRCxJQUFBLEVBQVEsU0FBUyxDQUFDO1VBRmpCLENBWEssQ0FBUjtVQWVBLEVBQUEsRUFBSyxDQUFBLENBQUUsb0JBQUY7UUFmTCxDQURjO01BckNoQjtJQURGLENBREY7V0EwREEsQ0FBQyxDQUFDLE1BQUYsQ0FBUyxTQUFTLENBQUMsS0FBbkIsRUFBMEIsUUFBMUI7RUFsRkEsQ0FBRjtBQUFBIiwiZmlsZSI6IndlYmNhc3Rlci5qcyIsInNvdXJjZXNDb250ZW50IjpbIm5hdmlnYXRvci5tZWRpYURldmljZXMgfHw9IHt9XG5cbm5hdmlnYXRvci5tZWRpYURldmljZXMuZ2V0VXNlck1lZGlhIHx8PSAoY29uc3RyYWludHMpIC0+XG4gIGZuID0gbmF2aWdhdG9yLmdldFVzZXJNZWRpYSB8fCBuYXZpZ2F0b3Iud2Via2l0R2V0VXNlck1lZGlhIHx8IG5hdmlnYXRvci5tb3pHZXRVc2VyTWVkaWEgfHwgbmF2aWdhdG9yLm1zR2V0VXNlck1lZGlhXG5cbiAgdW5sZXNzIGZuP1xuICAgIHJldHVybiBQcm9taXNlLnJlamVjdCBuZXcgRXJyb3IoXCJnZXRVc2VyTWVkaWEgaXMgbm90IGltcGxlbWVudGVkIGluIHRoaXMgYnJvd3NlclwiKVxuXG4gIG5ldyBQcm9taXNlIChyZXNvbHZlLCByZWplY3QpIC0+XG4gICAgZm4uY2FsbCBuYXZpZ2F0b3IsIGNvbnN0cmFpbnRzLCByZXNvbHZlLCByZWplY3RcblxubmF2aWdhdG9yLm1lZGlhRGV2aWNlcy5lbnVtZXJhdGVEZXZpY2VzIHx8PSAtPlxuICBQcm9taXNlLnJlamVjdCBuZXcgRXJyb3IoXCJlbnVtZXJhdGVEZXZpY2VzIGlzIG5vdCBpbXBsZW1lbnRlZCBvbiB0aGlzIGJyb3dzZXJcIilcbiIsIndpbmRvdy5XZWJjYXN0ZXIgPSBXZWJjYXN0ZXIgPVxuICBWaWV3OiB7fVxuICBNb2RlbDoge31cbiAgU291cmNlOiB7fVxuXG4gIHByZXR0aWZ5VGltZTogKHRpbWUpIC0+XG4gICAgaG91cnMgICA9IHBhcnNlSW50IHRpbWUgLyAzNjAwXG4gICAgdGltZSAgICU9IDM2MDBcbiAgICBtaW51dGVzID0gcGFyc2VJbnQgdGltZSAvIDYwXG4gICAgc2Vjb25kcyA9IHBhcnNlSW50IHRpbWUgJSA2MFxuXG4gICAgbWludXRlcyA9IFwiMCN7bWludXRlc31cIiBpZiBtaW51dGVzIDwgMTBcbiAgICBzZWNvbmRzID0gXCIwI3tzZWNvbmRzfVwiIGlmIHNlY29uZHMgPCAxMFxuXG4gICAgcmVzdWx0ID0gXCIje21pbnV0ZXN9OiN7c2Vjb25kc31cIlxuICAgIHJlc3VsdCA9IFwiI3tob3Vyc306I3tyZXN1bHR9XCIgaWYgaG91cnMgPiAwXG5cbiAgICByZXN1bHRcbiIsImNsYXNzIFdlYmNhc3Rlci5Ob2RlXG4gIF8uZXh0ZW5kIEBwcm90b3R5cGUsIEJhY2tib25lLkV2ZW50c1xuXG4gIGRlZmF1bHRDaGFubmVscyA9IDJcblxuICBjb25zdHJ1Y3RvcjogKHtAbW9kZWx9KSAtPlxuICAgIGlmIHR5cGVvZiB3ZWJraXRBdWRpb0NvbnRleHQgIT0gXCJ1bmRlZmluZWRcIlxuICAgICAgQGNvbnRleHQgPSBuZXcgd2Via2l0QXVkaW9Db250ZXh0XG4gICAgZWxzZVxuICAgICAgQGNvbnRleHQgPSBuZXcgQXVkaW9Db250ZXh0XG5cbiAgICBAd2ViY2FzdCA9IEBjb250ZXh0LmNyZWF0ZVdlYmNhc3RTb3VyY2UgNDA5NiwgZGVmYXVsdENoYW5uZWxzXG5cbiAgICBAY29ubmVjdCgpXG5cbiAgICBAbW9kZWwub24gXCJjaGFuZ2U6cGFzc1Rocm91Z2hcIiwgPT5cbiAgICAgIEB3ZWJjYXN0LnNldFBhc3NUaHJvdWdoIEBtb2RlbC5nZXQoXCJwYXNzVGhyb3VnaFwiKVxuXG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOmNoYW5uZWxzXCIsID0+XG4gICAgICBAcmVjb25uZWN0KClcblxuICBjb25uZWN0OiAtPlxuICAgIGlmIEBtb2RlbC5nZXQoXCJjaGFubmVsc1wiKSA9PSAxXG4gICAgICBAbWVyZ2VyIHx8PSBAY29udGV4dC5jcmVhdGVDaGFubmVsTWVyZ2VyIEBkZWZhdWx0Q2hhbm5lbHNcbiAgICAgIEBtZXJnZXIuY29ubmVjdCBAY29udGV4dC5kZXN0aW5hdGlvblxuICAgICAgQHdlYmNhc3QuY29ubmVjdCBAbWVyZ2VyXG4gICAgZWxzZVxuICAgICAgQHdlYmNhc3QuY29ubmVjdCBAY29udGV4dC5kZXN0aW5hdGlvbiAgIFxuXG4gIGRpc2Nvbm5lY3Q6IC0+XG4gICAgQHdlYmNhc3QuZGlzY29ubmVjdCgpXG4gICAgQG1lcmdlcj8uZGlzY29ubmVjdCgpXG5cbiAgcmVjb25uZWN0OiAtPlxuICAgIEBkaXNjb25uZWN0KClcbiAgICBAY29ubmVjdCgpXG5cbiAgc3RhcnRTdHJlYW06IC0+XG4gICAgc3dpdGNoIEBtb2RlbC5nZXQoXCJlbmNvZGVyXCIpXG4gICAgICB3aGVuIFwibXAzXCJcbiAgICAgICAgZW5jb2RlciA9IFdlYmNhc3QuRW5jb2Rlci5NcDNcbiAgICAgIHdoZW4gXCJyYXdcIlxuICAgICAgICBlbmNvZGVyID0gV2ViY2FzdC5FbmNvZGVyLlJhd1xuXG4gICAgQGVuY29kZXIgPSBuZXcgZW5jb2RlclxuICAgICAgY2hhbm5lbHMgICA6IEBtb2RlbC5nZXQoXCJjaGFubmVsc1wiKVxuICAgICAgc2FtcGxlcmF0ZSA6IEBtb2RlbC5nZXQoXCJzYW1wbGVyYXRlXCIpXG4gICAgICBiaXRyYXRlICAgIDogQG1vZGVsLmdldChcImJpdHJhdGVcIilcblxuICAgIGlmIEBtb2RlbC5nZXQoXCJzYW1wbGVyYXRlXCIpICE9IEBjb250ZXh0LnNhbXBsZVJhdGVcbiAgICAgIEBlbmNvZGVyID0gbmV3IFdlYmNhc3QuRW5jb2Rlci5SZXNhbXBsZVxuICAgICAgICBlbmNvZGVyICAgIDogQGVuY29kZXJcbiAgICAgICAgdHlwZSAgICAgICA6IFNhbXBsZXJhdGUuTElORUFSLFxuICAgICAgICBzYW1wbGVyYXRlIDogQGNvbnRleHQuc2FtcGxlUmF0ZVxuXG4gICAgaWYgQG1vZGVsLmdldChcImFzeW5jaHJvbm91c1wiKVxuICAgICAgQGVuY29kZXIgPSBuZXcgV2ViY2FzdC5FbmNvZGVyLkFzeW5jaHJvbm91c1xuICAgICAgICBlbmNvZGVyIDogQGVuY29kZXJcbiAgICAgICAgc2NyaXB0czogW1xuICAgICAgICAgIFwiaHR0cHM6Ly9jZG4ucmF3Z2l0LmNvbS93ZWJjYXN0L2xpYnNhbXBsZXJhdGUuanMvbWFzdGVyL2Rpc3QvbGlic2FtcGxlcmF0ZS5qc1wiLFxuICAgICAgICAgIFwiaHR0cHM6Ly9jZG4ucmF3Z2l0LmNvbS9zYXZvbmV0L3NoaW5lL21hc3Rlci9qcy9kaXN0L2xpYnNoaW5lLmpzXCIsXG4gICAgICAgICAgXCJodHRwczovL2Nkbi5yYXdnaXQuY29tL3dlYmNhc3Qvd2ViY2FzdC5qcy9tYXN0ZXIvbGliL3dlYmNhc3QuanNcIlxuICAgICAgICBdXG5cbiAgICBAd2ViY2FzdC5jb25uZWN0U29ja2V0IEBlbmNvZGVyLCBAbW9kZWwuZ2V0KFwidXJpXCIpXG5cbiAgc3RvcFN0cmVhbTogLT5cbiAgICBAd2ViY2FzdC5jbG9zZSgpXG5cbiAgY3JlYXRlQXVkaW9Tb3VyY2U6ICh7ZmlsZSwgYXVkaW99LCBtb2RlbCwgY2IpIC0+XG4gICAgZWwgPSBuZXcgQXVkaW8gVVJMLmNyZWF0ZU9iamVjdFVSTChmaWxlKVxuICAgIGVsLmNvbnRyb2xzID0gZmFsc2VcbiAgICBlbC5hdXRvcGxheSA9IGZhbHNlXG4gICAgZWwubG9vcCAgICAgPSBmYWxzZVxuXG4gICAgZWwuYWRkRXZlbnRMaXN0ZW5lciBcImVuZGVkXCIsID0+XG4gICAgICBtb2RlbC5vbkVuZCgpXG5cbiAgICBzb3VyY2UgPSBudWxsXG5cbiAgICBlbC5hZGRFdmVudExpc3RlbmVyIFwiY2FucGxheVwiLCA9PlxuICAgICAgcmV0dXJuIGlmIHNvdXJjZT9cblxuICAgICAgc291cmNlID0gQGNvbnRleHQuY3JlYXRlTWVkaWFFbGVtZW50U291cmNlIGVsXG5cbiAgICAgIHNvdXJjZS5wbGF5ID0gLT5cbiAgICAgICAgZWwucGxheSgpXG5cbiAgICAgIHNvdXJjZS5wb3NpdGlvbiA9IC0+XG4gICAgICAgIGVsLmN1cnJlbnRUaW1lXG5cbiAgICAgIHNvdXJjZS5kdXJhdGlvbiA9IC0+XG4gICAgICAgIGVsLmR1cmF0aW9uXG5cbiAgICAgIHNvdXJjZS5wYXVzZWQgPSAtPlxuICAgICAgICBlbC5wYXVzZWRcblxuICAgICAgc291cmNlLnN0b3AgPSAtPlxuICAgICAgICBlbC5wYXVzZSgpXG4gICAgICAgIGVsLnJlbW92ZSgpXG5cbiAgICAgIHNvdXJjZS5wYXVzZSA9IC0+XG4gICAgICAgIGVsLnBhdXNlKClcblxuICAgICAgc291cmNlLnNlZWsgPSAocGVyY2VudCkgLT5cbiAgICAgICAgdGltZSA9IHBlcmNlbnQqcGFyc2VGbG9hdChhdWRpby5sZW5ndGgpXG5cbiAgICAgICAgZWwuY3VycmVudFRpbWUgPSB0aW1lXG4gICAgICAgIHRpbWVcblxuICAgICAgY2Igc291cmNlXG5cbiAgY3JlYXRlRmlsZVNvdXJjZTogKGZpbGUsIG1vZGVsLCBjYikgLT5cbiAgICBAc291cmNlPy5kaXNjb25uZWN0KClcblxuICAgIEBjcmVhdGVBdWRpb1NvdXJjZSBmaWxlLCBtb2RlbCwgY2JcblxuICBjcmVhdGVNaWNyb3Bob25lU291cmNlOiAoY29uc3RyYWludHMsIGNiKSAtPlxuICAgIG5hdmlnYXRvci5tZWRpYURldmljZXMuZ2V0VXNlck1lZGlhKGNvbnN0cmFpbnRzKS50aGVuIChzdHJlYW0pID0+XG4gICAgICBzb3VyY2UgPSBAY29udGV4dC5jcmVhdGVNZWRpYVN0cmVhbVNvdXJjZSBzdHJlYW1cblxuICAgICAgc291cmNlLnN0b3AgPSAtPlxuICAgICAgICBzdHJlYW0uZ2V0QXVkaW9UcmFja3MoKT9bMF0uc3RvcCgpXG5cbiAgICAgIGNiIHNvdXJjZVxuXG4gIHNlbmRNZXRhZGF0YTogKGRhdGEpIC0+XG4gICAgQHdlYmNhc3Quc2VuZE1ldGFkYXRhIGRhdGFcblxuICBjbG9zZTogKGNiKSAtPlxuICAgIEB3ZWJjYXN0LmNsb3NlIGNiXG4iLCJjbGFzcyBXZWJjYXN0ZXIuVmlldy5UcmFjayBleHRlbmRzIEJhY2tib25lLlZpZXdcbiAgaW5pdGlhbGl6ZTogLT5cbiAgICBAbW9kZWwub24gXCJjaGFuZ2U6cGFzc1Rocm91Z2hcIiwgPT5cbiAgICAgIGlmIEBtb2RlbC5nZXQoXCJwYXNzVGhyb3VnaFwiKVxuICAgICAgICBAJChcIi5wYXNzVGhyb3VnaFwiKS5hZGRDbGFzcyhcImJ0bi1jdWVkXCIpLnJlbW92ZUNsYXNzIFwiYnRuLWluZm9cIlxuICAgICAgZWxzZVxuICAgICAgICBAJChcIi5wYXNzVGhyb3VnaFwiKS5hZGRDbGFzcyhcImJ0bi1pbmZvXCIpLnJlbW92ZUNsYXNzIFwiYnRuLWN1ZWRcIlxuXG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOnZvbHVtZUxlZnRcIiwgPT5cbiAgICAgIEAkKFwiLnZvbHVtZS1sZWZ0XCIpLndpZHRoIFwiI3tAbW9kZWwuZ2V0KFwidm9sdW1lTGVmdFwiKX0lXCJcblxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTp2b2x1bWVSaWdodFwiLCA9PlxuICAgICAgQCQoXCIudm9sdW1lLXJpZ2h0XCIpLndpZHRoIFwiI3tAbW9kZWwuZ2V0KFwidm9sdW1lUmlnaHRcIil9JVwiXG5cbiAgb25QYXNzVGhyb3VnaDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICBAbW9kZWwudG9nZ2xlUGFzc1Rocm91Z2goKVxuXG4gIG9uU3VibWl0OiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcbiIsImNsYXNzIFdlYmNhc3Rlci5WaWV3Lk1pY3JvcGhvbmUgZXh0ZW5kcyBXZWJjYXN0ZXIuVmlldy5UcmFja1xuICBldmVudHM6XG4gICAgXCJjbGljayAucmVjb3JkLWF1ZGlvXCIgICAgOiBcIm9uUmVjb3JkXCJcbiAgICBcImNsaWNrIC5wYXNzVGhyb3VnaFwiICAgICA6IFwib25QYXNzVGhyb3VnaFwiXG4gICAgXCJzdWJtaXRcIiAgICAgICAgICAgICAgICAgOiBcIm9uU3VibWl0XCJcblxuICBpbml0aWFsaXplOiAtPlxuICAgIHN1cGVyKClcblxuICAgIEBtb2RlbC5vbiBcInBsYXlpbmdcIiwgPT5cbiAgICAgIEAkKFwiLnBsYXktY29udHJvbFwiKS5yZW1vdmVBdHRyIFwiZGlzYWJsZWRcIlxuICAgICAgQCQoXCIucmVjb3JkLWF1ZGlvXCIpLmFkZENsYXNzIFwiYnRuLXJlY29yZGluZ1wiXG4gICAgICBAJChcIi52b2x1bWUtbGVmdFwiKS53aWR0aCBcIjAlXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1yaWdodFwiKS53aWR0aCBcIjAlXCJcblxuICAgIEBtb2RlbC5vbiBcInN0b3BwZWRcIiwgPT5cbiAgICAgIEAkKFwiLnJlY29yZC1hdWRpb1wiKS5yZW1vdmVDbGFzcyBcImJ0bi1yZWNvcmRpbmdcIlxuICAgICAgQCQoXCIudm9sdW1lLWxlZnRcIikud2lkdGggXCIwJVwiXG4gICAgICBAJChcIi52b2x1bWUtcmlnaHRcIikud2lkdGggXCIwJVwiXG5cbiAgcmVuZGVyOiAtPlxuICAgIEAkKFwiLm1pY3JvcGhvbmUtc2xpZGVyXCIpLnNsaWRlclxuICAgICAgb3JpZW50YXRpb246IFwidmVydGljYWxcIlxuICAgICAgbWluOiAwXG4gICAgICBtYXg6IDE1MFxuICAgICAgdmFsdWU6IDEwMFxuICAgICAgc3RvcDogPT5cbiAgICAgICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcCBcImhpZGVcIlxuICAgICAgc2xpZGU6IChlLCB1aSkgPT5cbiAgICAgICAgQG1vZGVsLnNldCB0cmFja0dhaW46IHVpLnZhbHVlXG4gICAgICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXAgXCJzaG93XCJcblxuICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXBcbiAgICAgIHRpdGxlOiA9PiBAbW9kZWwuZ2V0IFwidHJhY2tHYWluXCJcbiAgICAgIHRyaWdnZXI6IFwiXCJcbiAgICAgIGFuaW1hdGlvbjogZmFsc2VcbiAgICAgIHBsYWNlbWVudDogXCJsZWZ0XCJcblxuICAgIG5hdmlnYXRvci5tZWRpYURldmljZXMuZ2V0VXNlck1lZGlhKHthdWRpbzp0cnVlLCB2aWRlbzpmYWxzZX0pLnRoZW4gPT5cbiAgICAgIG5hdmlnYXRvci5tZWRpYURldmljZXMuZW51bWVyYXRlRGV2aWNlcygpLnRoZW4gKGRldmljZXMpID0+XG4gICAgICAgIGRldmljZXMgPSBfLmZpbHRlciBkZXZpY2VzLCAoe2tpbmQsIGRldmljZUlkfSkgLT5cbiAgICAgICAgICBraW5kID09IFwiYXVkaW9pbnB1dFwiXG5cbiAgICAgICAgcmV0dXJuIGlmIF8uaXNFbXB0eSBkZXZpY2VzXG5cbiAgICAgICAgJHNlbGVjdCA9IEAkKFwiLm1pY3JvcGhvbmUtZW50cnkgc2VsZWN0XCIpXG5cbiAgICAgICAgXy5lYWNoIGRldmljZXMsICh7bGFiZWwsZGV2aWNlSWR9KSAtPlxuICAgICAgICAgICRzZWxlY3QuYXBwZW5kIFwiPG9wdGlvbiB2YWx1ZT0nI3tkZXZpY2VJZH0nPiN7bGFiZWx9PC9vcHRpb24+XCJcblxuICAgICAgICAkc2VsZWN0LmZpbmQoXCJvcHRpb246ZXEoMClcIikucHJvcCBcInNlbGVjdGVkXCIsIHRydWVcblxuICAgICAgICBAbW9kZWwuc2V0IFwiZGV2aWNlXCIsICRzZWxlY3QudmFsKClcblxuICAgICAgICAkc2VsZWN0LnNlbGVjdCAtPlxuICAgICAgICAgIEBtb2RlbC5zZXQgXCJkZXZpY2VcIiwgJHNlbGVjdC52YWwoKVxuXG4gICAgICAgIEAkKFwiLm1pY3JvcGhvbmUtZW50cnlcIikuc2hvdygpXG5cbiAgICB0aGlzXG5cbiAgb25SZWNvcmQ6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgaWYgQG1vZGVsLmlzUGxheWluZygpXG4gICAgICByZXR1cm4gQG1vZGVsLnN0b3AoKVxuXG4gICAgQCQoXCIucGxheS1jb250cm9sXCIpLmF0dHIgZGlzYWJsZWQ6IFwiZGlzYWJsZWRcIlxuICAgIEBtb2RlbC5wbGF5KClcbiIsImNsYXNzIFdlYmNhc3Rlci5WaWV3Lk1peGVyIGV4dGVuZHMgQmFja2JvbmUuVmlld1xuICByZW5kZXI6IC0+XG4gICAgQCQoXCIuc2xpZGVyXCIpLnNsaWRlclxuICAgICAgc3RvcDogPT5cbiAgICAgICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcCBcImhpZGVcIlxuICAgICAgc2xpZGU6IChlLCB1aSkgPT5cbiAgICAgICAgQG1vZGVsLnNldCBzbGlkZXI6IHVpLnZhbHVlXG4gICAgICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXAgXCJzaG93XCJcblxuICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXBcbiAgICAgIHRpdGxlOiA9PiBAbW9kZWwuZ2V0IFwic2xpZGVyXCJcbiAgICAgIHRyaWdnZXI6IFwiXCJcbiAgICAgIGFuaW1hdGlvbjogZmFsc2VcbiAgICAgIHBsYWNlbWVudDogXCJib3R0b21cIlxuXG4gICAgdGhpc1xuIiwiY2xhc3MgV2ViY2FzdGVyLlZpZXcuUGxheWxpc3QgZXh0ZW5kcyBXZWJjYXN0ZXIuVmlldy5UcmFja1xuICBldmVudHM6XG4gICAgXCJjbGljayAucGxheS1hdWRpb1wiICAgICAgOiBcIm9uUGxheVwiXG4gICAgXCJjbGljayAucGF1c2UtYXVkaW9cIiAgICAgOiBcIm9uUGF1c2VcIlxuICAgIFwiY2xpY2sgLnByZXZpb3VzXCIgICAgICAgIDogXCJvblByZXZpb3VzXCJcbiAgICBcImNsaWNrIC5uZXh0XCIgICAgICAgICAgICA6IFwib25OZXh0XCJcbiAgICBcImNsaWNrIC5zdG9wXCIgICAgICAgICAgICA6IFwib25TdG9wXCJcbiAgICBcImNsaWNrIC5wcm9ncmVzcy1zZWVrXCIgICA6IFwib25TZWVrXCJcbiAgICBcImNsaWNrIC5wYXNzVGhyb3VnaFwiICAgICA6IFwib25QYXNzVGhyb3VnaFwiXG4gICAgXCJjaGFuZ2UgLmZpbGVzXCIgICAgICAgICAgOiBcIm9uRmlsZXNcIlxuICAgIFwiY2hhbmdlIC5wbGF5VGhyb3VnaFwiICAgIDogXCJvblBsYXlUaHJvdWdoXCJcbiAgICBcImNoYW5nZSAubG9vcFwiICAgICAgICAgICA6IFwib25Mb29wXCJcbiAgICBcInN1Ym1pdFwiICAgICAgICAgICAgICAgICA6IFwib25TdWJtaXRcIlxuXG4gIGluaXRpYWxpemU6IC0+XG4gICAgc3VwZXIoKVxuXG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOmZpbGVJbmRleFwiLCA9PlxuICAgICAgQCQoXCIudHJhY2stcm93XCIpLnJlbW92ZUNsYXNzIFwic3VjY2Vzc1wiXG4gICAgICBAJChcIi50cmFjay1yb3ctI3tAbW9kZWwuZ2V0KFwiZmlsZUluZGV4XCIpfVwiKS5hZGRDbGFzcyBcInN1Y2Nlc3NcIlxuXG4gICAgQG1vZGVsLm9uIFwicGxheWluZ1wiLCA9PlxuICAgICAgQCQoXCIucGxheS1jb250cm9sXCIpLnJlbW92ZUF0dHIgXCJkaXNhYmxlZFwiXG4gICAgICBAJChcIi5wbGF5LWF1ZGlvXCIpLmhpZGUoKVxuICAgICAgQCQoXCIucGF1c2UtYXVkaW9cIikuc2hvdygpXG4gICAgICBAJChcIi50cmFjay1wb3NpdGlvbi10ZXh0XCIpLnJlbW92ZUNsYXNzKFwiYmxpbmtcIikudGV4dCBcIlwiXG4gICAgICBAJChcIi52b2x1bWUtbGVmdFwiKS53aWR0aCBcIjAlXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1yaWdodFwiKS53aWR0aCBcIjAlXCJcblxuICAgICAgaWYgQG1vZGVsLmdldChcImR1cmF0aW9uXCIpXG4gICAgICAgIEAkKFwiLnByb2dyZXNzLXZvbHVtZVwiKS5jc3MgXCJjdXJzb3JcIiwgXCJwb2ludGVyXCJcbiAgICAgIGVsc2VcbiAgICAgICAgQCQoXCIudHJhY2stcG9zaXRpb25cIikuYWRkQ2xhc3MoXCJwcm9ncmVzcy1zdHJpcGVkIGFjdGl2ZVwiKVxuICAgICAgICBAc2V0VHJhY2tQcm9ncmVzcyAxMDBcblxuICAgIEBtb2RlbC5vbiBcInBhdXNlZFwiLCA9PlxuICAgICAgQCQoXCIucGxheS1hdWRpb1wiKS5zaG93KClcbiAgICAgIEAkKFwiLnBhdXNlLWF1ZGlvXCIpLmhpZGUoKVxuICAgICAgQCQoXCIudm9sdW1lLWxlZnRcIikud2lkdGggXCIwJVwiXG4gICAgICBAJChcIi52b2x1bWUtcmlnaHRcIikud2lkdGggXCIwJVwiXG4gICAgICBAJChcIi50cmFjay1wb3NpdGlvbi10ZXh0XCIpLmFkZENsYXNzIFwiYmxpbmtcIlxuXG4gICAgQG1vZGVsLm9uIFwic3RvcHBlZFwiLCA9PlxuICAgICAgQCQoXCIucGxheS1hdWRpb1wiKS5zaG93KClcbiAgICAgIEAkKFwiLnBhdXNlLWF1ZGlvXCIpLmhpZGUoKVxuICAgICAgQCQoXCIucHJvZ3Jlc3Mtdm9sdW1lXCIpLmNzcyBcImN1cnNvclwiLCBcIlwiXG4gICAgICBAJChcIi50cmFjay1wb3NpdGlvblwiKS5yZW1vdmVDbGFzcyhcInByb2dyZXNzLXN0cmlwZWQgYWN0aXZlXCIpXG4gICAgICBAc2V0VHJhY2tQcm9ncmVzcyAwXG4gICAgICBAJChcIi50cmFjay1wb3NpdGlvbi10ZXh0XCIpLnJlbW92ZUNsYXNzKFwiYmxpbmtcIikudGV4dCBcIlwiXG4gICAgICBAJChcIi52b2x1bWUtbGVmdFwiKS53aWR0aCBcIjAlXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1yaWdodFwiKS53aWR0aCBcIjAlXCJcblxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTpwb3NpdGlvblwiLCA9PlxuICAgICAgcmV0dXJuIHVubGVzcyBkdXJhdGlvbiA9IEBtb2RlbC5nZXQoXCJkdXJhdGlvblwiKVxuXG4gICAgICBwb3NpdGlvbiA9IHBhcnNlRmxvYXQgQG1vZGVsLmdldChcInBvc2l0aW9uXCIpXG5cbiAgICAgIEBzZXRUcmFja1Byb2dyZXNzIDEwMC4wKnBvc2l0aW9uL3BhcnNlRmxvYXQoZHVyYXRpb24pXG5cbiAgICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uLXRleHRcIikuXG4gICAgICAgIHRleHQgXCIje1dlYmNhc3Rlci5wcmV0dGlmeVRpbWUocG9zaXRpb24pfSAvICN7V2ViY2FzdGVyLnByZXR0aWZ5VGltZShkdXJhdGlvbil9XCJcblxuICByZW5kZXI6IC0+XG4gICAgQCQoXCIudm9sdW1lLXNsaWRlclwiKS5zbGlkZXJcbiAgICAgIG9yaWVudGF0aW9uOiBcInZlcnRpY2FsXCJcbiAgICAgIG1pbjogMFxuICAgICAgbWF4OiAxNTBcbiAgICAgIHZhbHVlOiAxMDBcbiAgICAgIHN0b3A6ID0+XG4gICAgICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXAgXCJoaWRlXCJcbiAgICAgIHNsaWRlOiAoZSwgdWkpID0+XG4gICAgICAgIEBtb2RlbC5zZXQgdHJhY2tHYWluOiB1aS52YWx1ZVxuICAgICAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwIFwic2hvd1wiXG5cbiAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwXG4gICAgICB0aXRsZTogPT4gQG1vZGVsLmdldCBcInRyYWNrR2FpblwiXG4gICAgICB0cmlnZ2VyOiBcIlwiXG4gICAgICBhbmltYXRpb246IGZhbHNlXG4gICAgICBwbGFjZW1lbnQ6IFwibGVmdFwiXG5cbiAgICBmaWxlcyA9IEBtb2RlbC5nZXQgXCJmaWxlc1wiXG5cbiAgICBAJChcIi5maWxlcy10YWJsZVwiKS5lbXB0eSgpXG5cbiAgICByZXR1cm4gdGhpcyB1bmxlc3MgZmlsZXMubGVuZ3RoID4gMFxuXG4gICAgXy5lYWNoIGZpbGVzLCAoe2ZpbGUsIGF1ZGlvLCBtZXRhZGF0YX0sIGluZGV4KSA9PlxuICAgICAgaWYgYXVkaW8/Lmxlbmd0aCAhPSAwXG4gICAgICAgIHRpbWUgPSBXZWJjYXN0ZXIucHJldHRpZnlUaW1lIGF1ZGlvLmxlbmd0aFxuICAgICAgZWxzZVxuICAgICAgICB0aW1lID0gXCJOL0FcIlxuXG4gICAgICBpZiBAbW9kZWwuZ2V0KFwiZmlsZUluZGV4XCIpID09IGluZGV4XG4gICAgICAgIGtsYXNzID0gXCJzdWNjZXNzXCJcbiAgICAgIGVsc2VcbiAgICAgICAga2xhc3MgPSBcIlwiXG4gICAgICAgIFxuICAgICAgQCQoXCIuZmlsZXMtdGFibGVcIikuYXBwZW5kIFwiXCJcIlxuICAgICAgICA8dHIgY2xhc3M9J3RyYWNrLXJvdyB0cmFjay1yb3ctI3tpbmRleH0gI3trbGFzc30nPlxuICAgICAgICAgIDx0ZD4je2luZGV4KzF9PC90ZD5cbiAgICAgICAgICA8dGQ+I3ttZXRhZGF0YT8udGl0bGUgfHwgXCJVbmtub3duIFRpdGxlXCJ9PC90ZD5cbiAgICAgICAgICA8dGQ+I3ttZXRhZGF0YT8uYXJ0aXN0IHx8IFwiVW5rbm93biBBcnRpc3RcIn08L3RkPlxuICAgICAgICAgIDx0ZD4je3RpbWV9PC90ZD5cbiAgICAgICAgPC90cj5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgXCJcIlwiXG5cbiAgICBAJChcIi5wbGF5bGlzdC10YWJsZVwiKS5zaG93KClcblxuICAgIHRoaXNcblxuICBzZXRUcmFja1Byb2dyZXNzOiAocGVyY2VudCkgLT5cbiAgICBAJChcIi50cmFjay1wb3NpdGlvblwiKS53aWR0aCBcIiN7cGVyY2VudCokKFwiLnByb2dyZXNzLXZvbHVtZVwiKS53aWR0aCgpLzEwMH1weFwiXG4gICAgQCQoXCIudHJhY2stcG9zaXRpb24tdGV4dCwucHJvZ3Jlc3Mtc2Vla1wiKS53aWR0aCAkKFwiLnByb2dyZXNzLXZvbHVtZVwiKS53aWR0aCgpXG5cbiAgcGxheTogKG9wdGlvbnMpIC0+XG4gICAgQG1vZGVsLnN0b3AoKVxuICAgIHJldHVybiB1bmxlc3MgQGZpbGUgPSBAbW9kZWwuc2VsZWN0RmlsZSBvcHRpb25zXG5cbiAgICBAJChcIi5wbGF5LWNvbnRyb2xcIikuYXR0ciBkaXNhYmxlZDogXCJkaXNhYmxlZFwiXG4gICAgQG1vZGVsLnBsYXkgQGZpbGVcblxuICBvblBsYXk6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuICAgIGlmIEBtb2RlbC5pc1BsYXlpbmcoKVxuICAgICAgQG1vZGVsLnRvZ2dsZVBhdXNlKClcbiAgICAgIHJldHVyblxuXG4gICAgQHBsYXkoKVxuXG4gIG9uUGF1c2U6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuICAgIEBtb2RlbC50b2dnbGVQYXVzZSgpXG5cbiAgb25QcmV2aW91czogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG4gICAgcmV0dXJuIHVubGVzcyBAbW9kZWwuaXNQbGF5aW5nKCk/XG5cbiAgICBAcGxheSBiYWNrd2FyZDogdHJ1ZVxuXG4gIG9uTmV4dDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG4gICAgcmV0dXJuIHVubGVzcyBAbW9kZWwuaXNQbGF5aW5nKClcblxuICAgIEBwbGF5KClcblxuICBvblN0b3A6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgQCQoXCIudHJhY2stcm93XCIpLnJlbW92ZUNsYXNzIFwic3VjY2Vzc1wiXG4gICAgQG1vZGVsLnN0b3AoKVxuICAgIEBmaWxlID0gbnVsbFxuXG4gIG9uU2VlazogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICBAbW9kZWwuc2VlayAoKGUucGFnZVggLSAkKGUudGFyZ2V0KS5vZmZzZXQoKS5sZWZ0KSAvICQoZS50YXJnZXQpLndpZHRoKCkpXG5cbiAgb25GaWxlczogLT5cbiAgICBmaWxlcyA9IEAkKFwiLmZpbGVzXCIpWzBdLmZpbGVzXG4gICAgQCQoXCIuZmlsZXNcIikuYXR0ciBkaXNhYmxlZDogXCJkaXNhYmxlZFwiXG5cbiAgICBAbW9kZWwuYXBwZW5kRmlsZXMgZmlsZXMsID0+XG4gICAgICBAJChcIi5maWxlc1wiKS5yZW1vdmVBdHRyKFwiZGlzYWJsZWRcIikudmFsIFwiXCJcbiAgICAgIEByZW5kZXIoKVxuXG4gIG9uUGxheVRocm91Z2g6IChlKSAtPlxuICAgIEBtb2RlbC5zZXQgcGxheVRocm91Z2g6ICQoZS50YXJnZXQpLmlzKFwiOmNoZWNrZWRcIilcblxuICBvbkxvb3A6IChlKSAtPlxuICAgIEBtb2RlbC5zZXQgbG9vcDogJChlLnRhcmdldCkuaXMoXCI6Y2hlY2tlZFwiKVxuIiwiY2xhc3MgV2ViY2FzdGVyLlZpZXcuU2V0dGluZ3MgZXh0ZW5kcyBCYWNrYm9uZS5WaWV3XG4gIGV2ZW50czpcbiAgICBcImNoYW5nZSAudXJpXCIgICAgICAgICAgICA6IFwib25VcmlcIlxuICAgIFwiY2hhbmdlIGlucHV0LmVuY29kZXJcIiAgIDogXCJvbkVuY29kZXJcIlxuICAgIFwiY2hhbmdlIGlucHV0LmNoYW5uZWxzXCIgIDogXCJvbkNoYW5uZWxzXCJcbiAgICBcImNoYW5nZSAuc2FtcGxlcmF0ZVwiICAgICA6IFwib25TYW1wbGVyYXRlXCJcbiAgICBcImNoYW5nZSAuYml0cmF0ZVwiICAgICAgICA6IFwib25CaXRyYXRlXCJcbiAgICBcImNoYW5nZSAuYXN5bmNocm9ub3VzXCIgICA6IFwib25Bc3luY2hyb25vdXNcIlxuICAgIFwiY2xpY2sgLnBhc3NUaHJvdWdoXCIgICAgIDogXCJvblBhc3NUaHJvdWdoXCJcbiAgICBcImNsaWNrIC5zdGFydC1zdHJlYW1cIiAgICA6IFwib25TdGFydFwiXG4gICAgXCJjbGljayAuc3RvcC1zdHJlYW1cIiAgICAgOiBcIm9uU3RvcFwiXG4gICAgXCJjbGljayAudXBkYXRlLW1ldGFkYXRhXCIgOiBcIm9uTWV0YWRhdGFVcGRhdGVcIlxuICAgIFwic3VibWl0XCIgICAgICAgICAgICAgICAgIDogXCJvblN1Ym1pdFwiXG5cbiAgaW5pdGlhbGl6ZTogKHtAbm9kZX0pIC0+XG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOnBhc3NUaHJvdWdoXCIsID0+XG4gICAgICBpZiBAbW9kZWwuZ2V0KFwicGFzc1Rocm91Z2hcIilcbiAgICAgICAgQCQoXCIucGFzc1Rocm91Z2hcIikuYWRkQ2xhc3MoXCJidG4tY3VlZFwiKS5yZW1vdmVDbGFzcyBcImJ0bi1pbmZvXCJcbiAgICAgIGVsc2VcbiAgICAgICAgQCQoXCIucGFzc1Rocm91Z2hcIikuYWRkQ2xhc3MoXCJidG4taW5mb1wiKS5yZW1vdmVDbGFzcyBcImJ0bi1jdWVkXCJcblxuICByZW5kZXI6IC0+XG4gICAgc2FtcGxlcmF0ZSA9IEBtb2RlbC5nZXQgXCJzYW1wbGVyYXRlXCJcbiAgICBAJChcIi5zYW1wbGVyYXRlXCIpLmVtcHR5KClcbiAgICBfLmVhY2ggQG1vZGVsLmdldChcInNhbXBsZXJhdGVzXCIpLCAocmF0ZSkgPT5cbiAgICAgIHNlbGVjdGVkID0gaWYgc2FtcGxlcmF0ZSA9PSByYXRlIHRoZW4gXCJzZWxlY3RlZFwiIGVsc2UgXCJcIlxuICAgICAgJChcIjxvcHRpb24gdmFsdWU9JyN7cmF0ZX0nICN7c2VsZWN0ZWR9PiN7cmF0ZX08L29wdGlvbj5cIikuXG4gICAgICAgIGFwcGVuZFRvIEAkKFwiLnNhbXBsZXJhdGVcIilcblxuICAgIGJpdHJhdGUgPSBAbW9kZWwuZ2V0IFwiYml0cmF0ZVwiXG4gICAgQCQoXCIuYml0cmF0ZVwiKS5lbXB0eSgpXG4gICAgXy5lYWNoIEBtb2RlbC5nZXQoXCJiaXRyYXRlc1wiKSwgKHJhdGUpID0+XG4gICAgICBzZWxlY3RlZCA9IGlmIGJpdHJhdGUgPT0gcmF0ZSB0aGVuIFwic2VsZWN0ZWRcIiBlbHNlIFwiXCJcbiAgICAgICQoXCI8b3B0aW9uIHZhbHVlPScje3JhdGV9JyAje3NlbGVjdGVkfT4je3JhdGV9PC9vcHRpb24+XCIpLlxuICAgICAgICBhcHBlbmRUbyBAJChcIi5iaXRyYXRlXCIpXG5cbiAgICB0aGlzXG5cbiAgb25Vcmk6IC0+XG4gICAgQG1vZGVsLnNldCB1cmk6IEAkKFwiLnVyaVwiKS52YWwoKVxuXG4gIG9uRW5jb2RlcjogKGUpIC0+XG4gICAgQG1vZGVsLnNldCBlbmNvZGVyOiAkKGUudGFyZ2V0KS52YWwoKVxuXG4gIG9uQ2hhbm5lbHM6IChlKSAtPlxuICAgIEBtb2RlbC5zZXQgY2hhbm5lbHM6IHBhcnNlSW50KCQoZS50YXJnZXQpLnZhbCgpKVxuXG4gIG9uU2FtcGxlcmF0ZTogKGUpIC0+XG4gICAgQG1vZGVsLnNldCBzYW1wbGVyYXRlOiBwYXJzZUludCgkKGUudGFyZ2V0KS52YWwoKSlcblxuICBvbkJpdHJhdGU6IChlKSAtPlxuICAgIEBtb2RlbC5zZXQgYml0cmF0ZTogcGFyc2VJbnQoJChlLnRhcmdldCkudmFsKCkpXG5cbiAgb25Bc3luY2hyb25vdXM6IChlKSAtPlxuICAgIEBtb2RlbC5zZXQgYXN5bmNocm9ub3VzOiAkKGUudGFyZ2V0KS5pcyhcIjpjaGVja2VkXCIpXG5cbiAgb25QYXNzVGhyb3VnaDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICBAbW9kZWwudG9nZ2xlUGFzc1Rocm91Z2goKVxuXG4gIG9uU3RhcnQ6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgQCQoXCIuc3RvcC1zdHJlYW1cIikuc2hvdygpXG4gICAgQCQoXCIuc3RhcnQtc3RyZWFtXCIpLmhpZGUoKVxuICAgIEAkKFwiaW5wdXQsIHNlbGVjdFwiKS5hdHRyIGRpc2FibGVkOiBcImRpc2FibGVkXCJcbiAgICBAJChcIi5tYW51YWwtbWV0YWRhdGEsIC51cGRhdGUtbWV0YWRhdGFcIikucmVtb3ZlQXR0ciBcImRpc2FibGVkXCJcblxuICAgIEBub2RlLnN0YXJ0U3RyZWFtKClcblxuICBvblN0b3A6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgQCQoXCIuc3RvcC1zdHJlYW1cIikuaGlkZSgpXG4gICAgQCQoXCIuc3RhcnQtc3RyZWFtXCIpLnNob3coKVxuICAgIEAkKFwiaW5wdXQsIHNlbGVjdFwiKS5yZW1vdmVBdHRyIFwiZGlzYWJsZWRcIlxuICAgIEAkKFwiLm1hbnVhbC1tZXRhZGF0YSwgLnVwZGF0ZS1tZXRhZGF0YVwiKS5hdHRyIGRpc2FibGVkOiBcImRpc2FibGVkXCJcblxuICAgIEBub2RlLnN0b3BTdHJlYW0oKVxuXG4gIG9uTWV0YWRhdGFVcGRhdGU6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgdGl0bGUgPSBAJChcIi5tYW51YWwtbWV0YWRhdGEuYXJ0aXN0XCIpLnZhbCgpXG4gICAgYXJ0aXN0ID0gQCQoXCIubWFudWFsLW1ldGFkYXRhLnRpdGxlXCIpLnZhbCgpXG5cbiAgICByZXR1cm4gdW5sZXNzIGFydGlzdCAhPSBcIlwiIHx8IHRpdGxlICE9IFwiXCJcblxuICAgIEBub2RlLnNlbmRNZXRhZGF0YVxuICAgICAgYXJ0aXN0OiBhcnRpc3RcbiAgICAgIHRpdGxlOiAgdGl0bGVcblxuICAgIEAkKFwiLm1ldGFkYXRhLXVwZGF0ZWRcIikuc2hvdyA0MDAsID0+XG4gICAgIGNiID0gPT5cbiAgICAgICBAJChcIi5tZXRhZGF0YS11cGRhdGVkXCIpLmhpZGUgNDAwXG5cbiAgICAgc2V0VGltZW91dCBjYiwgMjAwMFxuXG4gIG9uU3VibWl0OiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcbiIsIiQgLT5cbiAgV2ViY2FzdGVyLm1peGVyID0gbmV3IFdlYmNhc3Rlci5Nb2RlbC5NaXhlclxuICAgIHNsaWRlcjogMFxuXG4gIFdlYmNhc3Rlci5zZXR0aW5ncyA9IG5ldyBXZWJjYXN0ZXIuTW9kZWwuU2V0dGluZ3Moe1xuICAgIHVyaTogICAgICAgICAgXCJ3czovL3NvdXJjZTpoYWNrbWVAbG9jYWxob3N0OjgwODAvbW91bnRcIlxuICAgIGJpdHJhdGU6ICAgICAgMTI4XG4gICAgYml0cmF0ZXM6ICAgICBbIDgsIDE2LCAyNCwgMzIsIDQwLCA0OCwgNTYsXG4gICAgICAgICAgICAgICAgICAgIDY0LCA4MCwgOTYsIDExMiwgMTI4LCAxNDQsXG4gICAgICAgICAgICAgICAgICAgIDE2MCwgMTkyLCAyMjQsIDI1NiwgMzIwIF1cbiAgICBzYW1wbGVyYXRlOiAgIDQ0MTAwXG4gICAgc2FtcGxlcmF0ZXM6ICBbIDgwMDAsIDExMDI1LCAxMjAwMCwgMTYwMDAsXG4gICAgICAgICAgICAgICAgICAgIDIyMDUwLCAyNDAwMCwgMzIwMDAsIDQ0MTAwLCA0ODAwMCBdXG4gICAgY2hhbm5lbHM6ICAgICAyXG4gICAgZW5jb2RlcjogICAgICBcIm1wM1wiXG4gICAgYXN5bmNocm9ub3VzOiB0cnVlXG4gICAgcGFzc1Rocm91Z2g6ICBmYWxzZVxuICB9LCB7XG4gICAgbWl4ZXI6IFdlYmNhc3Rlci5taXhlclxuICB9KVxuXG4gIFdlYmNhc3Rlci5ub2RlID0gbmV3IFdlYmNhc3Rlci5Ob2RlXG4gICAgbW9kZWw6IFdlYmNhc3Rlci5zZXR0aW5nc1xuXG4gIF8uZXh0ZW5kIFdlYmNhc3RlcixcbiAgICB2aWV3czpcbiAgICAgIHNldHRpbmdzIDogbmV3IFdlYmNhc3Rlci5WaWV3LlNldHRpbmdzXG4gICAgICAgIG1vZGVsIDogV2ViY2FzdGVyLnNldHRpbmdzXG4gICAgICAgIG5vZGUgIDogV2ViY2FzdGVyLm5vZGVcbiAgICAgICAgZWwgICAgOiAkKFwiZGl2LnNldHRpbmdzXCIpXG5cbiAgICAgIG1peGVyOiBuZXcgV2ViY2FzdGVyLlZpZXcuTWl4ZXJcbiAgICAgICAgbW9kZWwgOiBXZWJjYXN0ZXIubWl4ZXJcbiAgICAgICAgZWwgICAgOiAkKFwiZGl2Lm1peGVyXCIpXG5cbiAgICAgIG1pY3JvcGhvbmU6IG5ldyBXZWJjYXN0ZXIuVmlldy5NaWNyb3Bob25lXG4gICAgICAgIG1vZGVsOiBuZXcgV2ViY2FzdGVyLk1vZGVsLk1pY3JvcGhvbmUoe1xuICAgICAgICAgIHRyYWNrR2FpbiAgIDogMTAwXG4gICAgICAgICAgcGFzc1Rocm91Z2ggOiBmYWxzZVxuICAgICAgICB9LCB7XG4gICAgICAgICAgbWl4ZXI6IFdlYmNhc3Rlci5taXhlclxuICAgICAgICAgIG5vZGU6ICBXZWJjYXN0ZXIubm9kZVxuICAgICAgICB9KVxuICAgICAgICBlbDogJChcImRpdi5taWNyb3Bob25lXCIpXG5cbiAgICAgIHBsYXlsaXN0TGVmdCA6IG5ldyBXZWJjYXN0ZXIuVmlldy5QbGF5bGlzdFxuICAgICAgICBtb2RlbCA6IG5ldyBXZWJjYXN0ZXIuTW9kZWwuUGxheWxpc3Qoe1xuICAgICAgICAgIHNpZGUgICAgICAgIDogXCJsZWZ0XCJcbiAgICAgICAgICBmaWxlcyAgICAgICA6IFtdXG4gICAgICAgICAgZmlsZUluZGV4ICAgOiAtMVxuICAgICAgICAgIHZvbHVtZUxlZnQgIDogMFxuICAgICAgICAgIHZvbHVtZVJpZ2h0IDogMFxuICAgICAgICAgIHRyYWNrR2FpbiAgIDogMTAwXG4gICAgICAgICAgcGFzc1Rocm91Z2ggOiBmYWxzZVxuICAgICAgICAgIHBsYXlUaHJvdWdoIDogdHJ1ZVxuICAgICAgICAgIHBvc2l0aW9uICAgIDogMC4wXG4gICAgICAgICAgbG9vcCAgICAgICAgOiBmYWxzZVxuICAgICAgICB9LCB7XG4gICAgICAgICAgbWl4ZXIgOiBXZWJjYXN0ZXIubWl4ZXJcbiAgICAgICAgICBub2RlICA6IFdlYmNhc3Rlci5ub2RlXG4gICAgICAgIH0pXG4gICAgICAgIGVsIDogJChcImRpdi5wbGF5bGlzdC1sZWZ0XCIpXG5cbiAgICAgIHBsYXlsaXN0UmlnaHQgOiBuZXcgV2ViY2FzdGVyLlZpZXcuUGxheWxpc3RcbiAgICAgICAgbW9kZWwgOiBuZXcgV2ViY2FzdGVyLk1vZGVsLlBsYXlsaXN0KHtcbiAgICAgICAgICBzaWRlICAgICAgICA6IFwicmlnaHRcIlxuICAgICAgICAgIGZpbGVzICAgICAgIDogW11cbiAgICAgICAgICBmaWxlSW5kZXggICA6IC0xXG4gICAgICAgICAgdm9sdW1lTGVmdCAgOiAwXG4gICAgICAgICAgdm9sdW1lUmlnaHQgOiAwXG4gICAgICAgICAgdHJhY2tHYWluICAgOiAxMDBcbiAgICAgICAgICBwYXNzVGhyb3VnaCA6IGZhbHNlXG4gICAgICAgICAgcGxheVRocm91Z2ggOiB0cnVlXG4gICAgICAgICAgcG9zaXRpb24gICAgOiAwLjBcbiAgICAgICAgICBsb29wICAgICAgICA6IGZhbHNlXG4gICAgICAgIH0sIHtcbiAgICAgICAgICBtaXhlciA6IFdlYmNhc3Rlci5taXhlclxuICAgICAgICAgIG5vZGUgIDogV2ViY2FzdGVyLm5vZGVcbiAgICAgICAgfSlcbiAgICAgICAgZWwgOiAkKFwiZGl2LnBsYXlsaXN0LXJpZ2h0XCIpXG5cblxuICBfLmludm9rZSBXZWJjYXN0ZXIudmlld3MsIFwicmVuZGVyXCJcbiJdfQ==
