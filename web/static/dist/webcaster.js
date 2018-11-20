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

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImNvbXBhdC5jb2ZmZWUiLCJ3ZWJjYXN0ZXIuY29mZmVlIiwibm9kZS5jb2ZmZWUiLCJ0cmFjay5jb2ZmZWUiLCJtaWNyb3Bob25lLmNvZmZlZSIsIm1peGVyLmNvZmZlZSIsInBsYXlsaXN0LmNvZmZlZSIsInNldHRpbmdzLmNvZmZlZSIsImluaXQuY29mZmVlIl0sIm5hbWVzIjpbXSwibWFwcGluZ3MiOiJBQUFBO0FBQUEsTUFBQSxJQUFBLEVBQUE7O0VBQUEsU0FBUyxDQUFDLGlCQUFWLFNBQVMsQ0FBQyxlQUFpQixDQUFBOztVQUUzQixTQUFTLENBQUMsYUFBWSxDQUFDLHFCQUFELENBQUMsZUFBaUIsUUFBQSxDQUFDLFdBQUQsQ0FBQTtBQUN0QyxRQUFBO0lBQUEsRUFBQSxHQUFLLFNBQVMsQ0FBQyxZQUFWLElBQTBCLFNBQVMsQ0FBQyxrQkFBcEMsSUFBMEQsU0FBUyxDQUFDLGVBQXBFLElBQXVGLFNBQVMsQ0FBQztJQUV0RyxJQUFPLFVBQVA7QUFDRSxhQUFPLE9BQU8sQ0FBQyxNQUFSLENBQWUsSUFBSSxLQUFKLENBQVUsaURBQVYsQ0FBZixFQURUOztXQUdBLElBQUksT0FBSixDQUFZLFFBQUEsQ0FBQyxPQUFELEVBQVUsTUFBVixDQUFBO2FBQ1YsRUFBRSxDQUFDLElBQUgsQ0FBUSxTQUFSLEVBQW1CLFdBQW5CLEVBQWdDLE9BQWhDLEVBQXlDLE1BQXpDO0lBRFUsQ0FBWjtFQU5zQzs7V0FTeEMsU0FBUyxDQUFDLGFBQVksQ0FBQywwQkFBRCxDQUFDLG1CQUFxQixRQUFBLENBQUEsQ0FBQTtXQUMxQyxPQUFPLENBQUMsTUFBUixDQUFlLElBQUksS0FBSixDQUFVLHFEQUFWLENBQWY7RUFEMEM7QUFYNUM7OztBQ0FBO0FBQUEsTUFBQTs7RUFBQSxNQUFNLENBQUMsU0FBUCxHQUFtQixTQUFBLEdBQ2pCO0lBQUEsSUFBQSxFQUFNLENBQUEsQ0FBTjtJQUNBLEtBQUEsRUFBTyxDQUFBLENBRFA7SUFFQSxNQUFBLEVBQVEsQ0FBQSxDQUZSO0lBSUEs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QUFTLENBQUMsSUFBSSxDQUFDLFFBQW5CLENBQ2Q7VUFBQSxLQUFBLEVBQVEsSUFBSSxTQUFTLENBQUMsS0FBSyxDQUFDLFFBQXBCLENBQTZCO1lBQ25DLElBQUEsRUFBYyxPQURxQjtZQUVuQyxLQUFBLEVBQWMsRUFGcUI7WUFHbkMsU0FBQSxFQUFjLENBQUMsQ0FIb0I7WUFJbkMsVUFBQSxFQUFjLENBSnFCO1lBS25DLFdBQUEsRUFBYyxDQUxxQjtZQU1uQyxTQUFBLEVBQWMsR0FOcUI7WUFPbkMsV0FBQSxFQUFjLEtBUHFCO1lBUW5DLFdBQUEsRUFBYyxJQVJxQjtZQVNuQyxRQUFBLEVBQWMsR0FUcUI7WUFVbkMsSUFBQSxFQUFjO1VBVnFCLENBQTdCLEVBV0w7WUFDRCxLQUFBLEVBQVEsU0FBUyxDQUFDLEtBRGpCO1lBRUQsSUFBQSxFQUFRLFNBQVMsQ0FBQztVQUZqQixDQVhLLENBQVI7VUFlQSxFQUFBLEVBQUssQ0FBQSxDQUFFLG9CQUFGO1FBZkwsQ0FEYztNQXJDaEI7SUFERixDQURGO1dBMERBLENBQUMsQ0FBQyxNQUFGLENBQVMsU0FBUyxDQUFDLEtBQW5CLEVBQTBCLFFBQTFCO0VBbEZBLENBQUY7QUFBQSIsImZpbGUiOiJ3ZWJjYXN0ZXIuanMiLCJzb3VyY2VzQ29udGVudCI6WyJuYXZpZ2F0b3IubWVkaWFEZXZpY2VzIHx8PSB7fVxuXG5uYXZpZ2F0b3IubWVkaWFEZXZpY2VzLmdldFVzZXJNZWRpYSB8fD0gKGNvbnN0cmFpbnRzKSAtPlxuICBmbiA9IG5hdmlnYXRvci5nZXRVc2VyTWVkaWEgfHwgbmF2aWdhdG9yLndlYmtpdEdldFVzZXJNZWRpYSB8fCBuYXZpZ2F0b3IubW96R2V0VXNlck1lZGlhIHx8IG5hdmlnYXRvci5tc0dldFVzZXJNZWRpYVxuXG4gIHVubGVzcyBmbj9cbiAgICByZXR1cm4gUHJvbWlzZS5yZWplY3QgbmV3IEVycm9yKFwiZ2V0VXNlck1lZGlhIGlzIG5vdCBpbXBsZW1lbnRlZCBpbiB0aGlzIGJyb3dzZXJcIilcblxuICBuZXcgUHJvbWlzZSAocmVzb2x2ZSwgcmVqZWN0KSAtPlxuICAgIGZuLmNhbGwgbmF2aWdhdG9yLCBjb25zdHJhaW50cywgcmVzb2x2ZSwgcmVqZWN0XG5cbm5hdmlnYXRvci5tZWRpYURldmljZXMuZW51bWVyYXRlRGV2aWNlcyB8fD0gLT5cbiAgUHJvbWlzZS5yZWplY3QgbmV3IEVycm9yKFwiZW51bWVyYXRlRGV2aWNlcyBpcyBub3QgaW1wbGVtZW50ZWQgb24gdGhpcyBicm93c2VyXCIpXG4iLCJ3aW5kb3cuV2ViY2FzdGVyID0gV2ViY2FzdGVyID1cbiAgVmlldzoge31cbiAgTW9kZWw6IHt9XG4gIFNvdXJjZToge31cblxuICBwcmV0dGlmeVRpbWU6ICh0aW1lKSAtPlxuICAgIGhvdXJzICAgPSBwYXJzZUludCB0aW1lIC8gMzYwMFxuICAgIHRpbWUgICAlPSAzNjAwXG4gICAgbWludXRlcyA9IHBhcnNlSW50IHRpbWUgLyA2MFxuICAgIHNlY29uZHMgPSBwYXJzZUludCB0aW1lICUgNjBcblxuICAgIG1pbnV0ZXMgPSBcIjAje21pbnV0ZXN9XCIgaWYgbWludXRlcyA8IDEwXG4gICAgc2Vjb25kcyA9IFwiMCN7c2Vjb25kc31cIiBpZiBzZWNvbmRzIDwgMTBcblxuICAgIHJlc3VsdCA9IFwiI3ttaW51dGVzfToje3NlY29uZHN9XCJcbiAgICByZXN1bHQgPSBcIiN7aG91cnN9OiN7cmVzdWx0fVwiIGlmIGhvdXJzID4gMFxuXG4gICAgcmVzdWx0XG4iLCJjbGFzcyBXZWJjYXN0ZXIuTm9kZVxuICBfLmV4dGVuZCBAcHJvdG90eXBlLCBCYWNrYm9uZS5FdmVudHNcblxuICBkZWZhdWx0Q2hhbm5lbHMgPSAyXG5cbiAgY29uc3RydWN0b3I6ICh7QG1vZGVsfSkgLT5cbiAgICBpZiB0eXBlb2Ygd2Via2l0QXVkaW9Db250ZXh0ICE9IFwidW5kZWZpbmVkXCJcbiAgICAgIEBjb250ZXh0ID0gbmV3IHdlYmtpdEF1ZGlvQ29udGV4dFxuICAgIGVsc2VcbiAgICAgIEBjb250ZXh0ID0gbmV3IEF1ZGlvQ29udGV4dFxuXG4gICAgQHdlYmNhc3QgPSBAY29udGV4dC5jcmVhdGVXZWJjYXN0U291cmNlIDQwOTYsIGRlZmF1bHRDaGFubmVsc1xuXG4gICAgQGNvbm5lY3QoKVxuXG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOnBhc3NUaHJvdWdoXCIsID0+XG4gICAgICBAd2ViY2FzdC5zZXRQYXNzVGhyb3VnaCBAbW9kZWwuZ2V0KFwicGFzc1Rocm91Z2hcIilcblxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTpjaGFubmVsc1wiLCA9PlxuICAgICAgQHJlY29ubmVjdCgpXG5cbiAgY29ubmVjdDogLT5cbiAgICBpZiBAbW9kZWwuZ2V0KFwiY2hhbm5lbHNcIikgPT0gMVxuICAgICAgQG1lcmdlciB8fD0gQGNvbnRleHQuY3JlYXRlQ2hhbm5lbE1lcmdlciBAZGVmYXVsdENoYW5uZWxzXG4gICAgICBAbWVyZ2VyLmNvbm5lY3QgQGNvbnRleHQuZGVzdGluYXRpb25cbiAgICAgIEB3ZWJjYXN0LmNvbm5lY3QgQG1lcmdlclxuICAgIGVsc2VcbiAgICAgIEB3ZWJjYXN0LmNvbm5lY3QgQGNvbnRleHQuZGVzdGluYXRpb24gICBcblxuICBkaXNjb25uZWN0OiAtPlxuICAgIEB3ZWJjYXN0LmRpc2Nvbm5lY3QoKVxuICAgIEBtZXJnZXI/LmRpc2Nvbm5lY3QoKVxuXG4gIHJlY29ubmVjdDogLT5cbiAgICBAZGlzY29ubmVjdCgpXG4gICAgQGNvbm5lY3QoKVxuXG4gIHN0YXJ0U3RyZWFtOiAtPlxuICAgIHN3aXRjaCBAbW9kZWwuZ2V0KFwiZW5jb2RlclwiKVxuICAgICAgd2hlbiBcIm1wM1wiXG4gICAgICAgIGVuY29kZXIgPSBXZWJjYXN0LkVuY29kZXIuTXAzXG4gICAgICB3aGVuIFwicmF3XCJcbiAgICAgICAgZW5jb2RlciA9IFdlYmNhc3QuRW5jb2Rlci5SYXdcblxuICAgIEBlbmNvZGVyID0gbmV3IGVuY29kZXJcbiAgICAgIGNoYW5uZWxzICAgOiBAbW9kZWwuZ2V0KFwiY2hhbm5lbHNcIilcbiAgICAgIHNhbXBsZXJhdGUgOiBAbW9kZWwuZ2V0KFwic2FtcGxlcmF0ZVwiKVxuICAgICAgYml0cmF0ZSAgICA6IEBtb2RlbC5nZXQoXCJiaXRyYXRlXCIpXG5cbiAgICBpZiBAbW9kZWwuZ2V0KFwic2FtcGxlcmF0ZVwiKSAhPSBAY29udGV4dC5zYW1wbGVSYXRlXG4gICAgICBAZW5jb2RlciA9IG5ldyBXZWJjYXN0LkVuY29kZXIuUmVzYW1wbGVcbiAgICAgICAgZW5jb2RlciAgICA6IEBlbmNvZGVyXG4gICAgICAgIHR5cGUgICAgICAgOiBTYW1wbGVyYXRlLkxJTkVBUixcbiAgICAgICAgc2FtcGxlcmF0ZSA6IEBjb250ZXh0LnNhbXBsZVJhdGVcblxuICAgIGlmIEBtb2RlbC5nZXQoXCJhc3luY2hyb25vdXNcIilcbiAgICAgIEBlbmNvZGVyID0gbmV3IFdlYmNhc3QuRW5jb2Rlci5Bc3luY2hyb25vdXNcbiAgICAgICAgZW5jb2RlciA6IEBlbmNvZGVyXG4gICAgICAgIHNjcmlwdHM6IFtcbiAgICAgICAgICBcImh0dHBzOi8vY2RuLnJhd2dpdC5jb20vd2ViY2FzdC9saWJzYW1wbGVyYXRlLmpzL21hc3Rlci9kaXN0L2xpYnNhbXBsZXJhdGUuanNcIixcbiAgICAgICAgICBcImh0dHBzOi8vY2RuLnJhd2dpdC5jb20vc2F2b25ldC9zaGluZS9tYXN0ZXIvanMvZGlzdC9saWJzaGluZS5qc1wiLFxuICAgICAgICAgIFwiaHR0cHM6Ly9jZG4ucmF3Z2l0LmNvbS93ZWJjYXN0L3dlYmNhc3QuanMvbWFzdGVyL2xpYi93ZWJjYXN0LmpzXCJcbiAgICAgICAgXVxuXG4gICAgQHdlYmNhc3QuY29ubmVjdFNvY2tldCBAZW5jb2RlciwgQG1vZGVsLmdldChcInVyaVwiKVxuXG4gIHN0b3BTdHJlYW06IC0+XG4gICAgQHdlYmNhc3QuY2xvc2UoKVxuXG4gIGNyZWF0ZUF1ZGlvU291cmNlOiAoe2ZpbGUsIGF1ZGlvfSwgbW9kZWwsIGNiKSAtPlxuICAgIGVsID0gbmV3IEF1ZGlvIFVSTC5jcmVhdGVPYmplY3RVUkwoZmlsZSlcbiAgICBlbC5jb250cm9scyA9IGZhbHNlXG4gICAgZWwuYXV0b3BsYXkgPSBmYWxzZVxuICAgIGVsLmxvb3AgICAgID0gZmFsc2VcblxuICAgIGVsLmFkZEV2ZW50TGlzdGVuZXIgXCJlbmRlZFwiLCA9PlxuICAgICAgbW9kZWwub25FbmQoKVxuXG4gICAgc291cmNlID0gbnVsbFxuXG4gICAgZWwuYWRkRXZlbnRMaXN0ZW5lciBcImNhbnBsYXlcIiwgPT5cbiAgICAgIHJldHVybiBpZiBzb3VyY2U/XG5cbiAgICAgIHNvdXJjZSA9IEBjb250ZXh0LmNyZWF0ZU1lZGlhRWxlbWVudFNvdXJjZSBlbFxuXG4gICAgICBzb3VyY2UucGxheSA9IC0+XG4gICAgICAgIGVsLnBsYXkoKVxuXG4gICAgICBzb3VyY2UucG9zaXRpb24gPSAtPlxuICAgICAgICBlbC5jdXJyZW50VGltZVxuXG4gICAgICBzb3VyY2UuZHVyYXRpb24gPSAtPlxuICAgICAgICBlbC5kdXJhdGlvblxuXG4gICAgICBzb3VyY2UucGF1c2VkID0gLT5cbiAgICAgICAgZWwucGF1c2VkXG5cbiAgICAgIHNvdXJjZS5zdG9wID0gLT5cbiAgICAgICAgZWwucGF1c2UoKVxuICAgICAgICBlbC5yZW1vdmUoKVxuXG4gICAgICBzb3VyY2UucGF1c2UgPSAtPlxuICAgICAgICBlbC5wYXVzZSgpXG5cbiAgICAgIHNvdXJjZS5zZWVrID0gKHBlcmNlbnQpIC0+XG4gICAgICAgIHRpbWUgPSBwZXJjZW50KnBhcnNlRmxvYXQoYXVkaW8ubGVuZ3RoKVxuXG4gICAgICAgIGVsLmN1cnJlbnRUaW1lID0gdGltZVxuICAgICAgICB0aW1lXG5cbiAgICAgIGNiIHNvdXJjZVxuXG4gIGNyZWF0ZUZpbGVTb3VyY2U6IChmaWxlLCBtb2RlbCwgY2IpIC0+XG4gICAgQHNvdXJjZT8uZGlzY29ubmVjdCgpXG5cbiAgICBAY3JlYXRlQXVkaW9Tb3VyY2UgZmlsZSwgbW9kZWwsIGNiXG5cbiAgY3JlYXRlTWljcm9waG9uZVNvdXJjZTogKGNvbnN0cmFpbnRzLCBjYikgLT5cbiAgICBuYXZpZ2F0b3IubWVkaWFEZXZpY2VzLmdldFVzZXJNZWRpYShjb25zdHJhaW50cykudGhlbiAoc3RyZWFtKSA9PlxuICAgICAgc291cmNlID0gQGNvbnRleHQuY3JlYXRlTWVkaWFTdHJlYW1Tb3VyY2Ugc3RyZWFtXG5cbiAgICAgIHNvdXJjZS5zdG9wID0gLT5cbiAgICAgICAgc3RyZWFtLmdldEF1ZGlvVHJhY2tzKCk/WzBdLnN0b3AoKVxuXG4gICAgICBjYiBzb3VyY2VcblxuICBzZW5kTWV0YWRhdGE6IChkYXRhKSAtPlxuICAgIEB3ZWJjYXN0LnNlbmRNZXRhZGF0YSBkYXRhXG5cbiAgY2xvc2U6IChjYikgLT5cbiAgICBAd2ViY2FzdC5jbG9zZSBjYlxuIiwiY2xhc3MgV2ViY2FzdGVyLlZpZXcuVHJhY2sgZXh0ZW5kcyBCYWNrYm9uZS5WaWV3XG4gIGluaXRpYWxpemU6IC0+XG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOnBhc3NUaHJvdWdoXCIsID0+XG4gICAgICBpZiBAbW9kZWwuZ2V0KFwicGFzc1Rocm91Z2hcIilcbiAgICAgICAgQCQoXCIucGFzc1Rocm91Z2hcIikuYWRkQ2xhc3MoXCJidG4tY3VlZFwiKS5yZW1vdmVDbGFzcyBcImJ0bi1pbmZvXCJcbiAgICAgIGVsc2VcbiAgICAgICAgQCQoXCIucGFzc1Rocm91Z2hcIikuYWRkQ2xhc3MoXCJidG4taW5mb1wiKS5yZW1vdmVDbGFzcyBcImJ0bi1jdWVkXCJcblxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTp2b2x1bWVMZWZ0XCIsID0+XG4gICAgICBAJChcIi52b2x1bWUtbGVmdFwiKS53aWR0aCBcIiN7QG1vZGVsLmdldChcInZvbHVtZUxlZnRcIil9JVwiXG5cbiAgICBAbW9kZWwub24gXCJjaGFuZ2U6dm9sdW1lUmlnaHRcIiwgPT5cbiAgICAgIEAkKFwiLnZvbHVtZS1yaWdodFwiKS53aWR0aCBcIiN7QG1vZGVsLmdldChcInZvbHVtZVJpZ2h0XCIpfSVcIlxuXG4gIG9uUGFzc1Rocm91Z2g6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgQG1vZGVsLnRvZ2dsZVBhc3NUaHJvdWdoKClcblxuICBvblN1Ym1pdDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG4iLCJjbGFzcyBXZWJjYXN0ZXIuVmlldy5NaWNyb3Bob25lIGV4dGVuZHMgV2ViY2FzdGVyLlZpZXcuVHJhY2tcbiAgZXZlbnRzOlxuICAgIFwiY2xpY2sgLnJlY29yZC1hdWRpb1wiICAgIDogXCJvblJlY29yZFwiXG4gICAgXCJjbGljayAucGFzc1Rocm91Z2hcIiAgICAgOiBcIm9uUGFzc1Rocm91Z2hcIlxuICAgIFwic3VibWl0XCIgICAgICAgICAgICAgICAgIDogXCJvblN1Ym1pdFwiXG5cbiAgaW5pdGlhbGl6ZTogLT5cbiAgICBzdXBlcigpXG5cbiAgICBAbW9kZWwub24gXCJwbGF5aW5nXCIsID0+XG4gICAgICBAJChcIi5wbGF5LWNvbnRyb2xcIikucmVtb3ZlQXR0ciBcImRpc2FibGVkXCJcbiAgICAgIEAkKFwiLnJlY29yZC1hdWRpb1wiKS5hZGRDbGFzcyBcImJ0bi1yZWNvcmRpbmdcIlxuICAgICAgQCQoXCIudm9sdW1lLWxlZnRcIikud2lkdGggXCIwJVwiXG4gICAgICBAJChcIi52b2x1bWUtcmlnaHRcIikud2lkdGggXCIwJVwiXG5cbiAgICBAbW9kZWwub24gXCJzdG9wcGVkXCIsID0+XG4gICAgICBAJChcIi5yZWNvcmQtYXVkaW9cIikucmVtb3ZlQ2xhc3MgXCJidG4tcmVjb3JkaW5nXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1sZWZ0XCIpLndpZHRoIFwiMCVcIlxuICAgICAgQCQoXCIudm9sdW1lLXJpZ2h0XCIpLndpZHRoIFwiMCVcIlxuXG4gIHJlbmRlcjogLT5cbiAgICBAJChcIi5taWNyb3Bob25lLXNsaWRlclwiKS5zbGlkZXJcbiAgICAgIG9yaWVudGF0aW9uOiBcInZlcnRpY2FsXCJcbiAgICAgIG1pbjogMFxuICAgICAgbWF4OiAxNTBcbiAgICAgIHZhbHVlOiAxMDBcbiAgICAgIHN0b3A6ID0+XG4gICAgICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXAgXCJoaWRlXCJcbiAgICAgIHNsaWRlOiAoZSwgdWkpID0+XG4gICAgICAgIEBtb2RlbC5zZXQgdHJhY2tHYWluOiB1aS52YWx1ZVxuICAgICAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwIFwic2hvd1wiXG5cbiAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwXG4gICAgICB0aXRsZTogPT4gQG1vZGVsLmdldCBcInRyYWNrR2FpblwiXG4gICAgICB0cmlnZ2VyOiBcIlwiXG4gICAgICBhbmltYXRpb246IGZhbHNlXG4gICAgICBwbGFjZW1lbnQ6IFwibGVmdFwiXG5cbiAgICBuYXZpZ2F0b3IubWVkaWFEZXZpY2VzLmdldFVzZXJNZWRpYSh7YXVkaW86dHJ1ZSwgdmlkZW86ZmFsc2V9KS50aGVuID0+XG4gICAgICBuYXZpZ2F0b3IubWVkaWFEZXZpY2VzLmVudW1lcmF0ZURldmljZXMoKS50aGVuIChkZXZpY2VzKSA9PlxuICAgICAgICBkZXZpY2VzID0gXy5maWx0ZXIgZGV2aWNlcywgKHtraW5kLCBkZXZpY2VJZH0pIC0+XG4gICAgICAgICAga2luZCA9PSBcImF1ZGlvaW5wdXRcIlxuXG4gICAgICAgIHJldHVybiBpZiBfLmlzRW1wdHkgZGV2aWNlc1xuXG4gICAgICAgICRzZWxlY3QgPSBAJChcIi5taWNyb3Bob25lLWVudHJ5IHNlbGVjdFwiKVxuXG4gICAgICAgIF8uZWFjaCBkZXZpY2VzLCAoe2xhYmVsLGRldmljZUlkfSkgLT5cbiAgICAgICAgICAkc2VsZWN0LmFwcGVuZCBcIjxvcHRpb24gdmFsdWU9JyN7ZGV2aWNlSWR9Jz4je2xhYmVsfTwvb3B0aW9uPlwiXG5cbiAgICAgICAgJHNlbGVjdC5maW5kKFwib3B0aW9uOmVxKDApXCIpLnByb3AgXCJzZWxlY3RlZFwiLCB0cnVlXG5cbiAgICAgICAgQG1vZGVsLnNldCBcImRldmljZVwiLCAkc2VsZWN0LnZhbCgpXG5cbiAgICAgICAgJHNlbGVjdC5zZWxlY3QgLT5cbiAgICAgICAgICBAbW9kZWwuc2V0IFwiZGV2aWNlXCIsICRzZWxlY3QudmFsKClcblxuICAgICAgICBAJChcIi5taWNyb3Bob25lLWVudHJ5XCIpLnNob3coKVxuXG4gICAgdGhpc1xuXG4gIG9uUmVjb3JkOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcblxuICAgIGlmIEBtb2RlbC5pc1BsYXlpbmcoKVxuICAgICAgcmV0dXJuIEBtb2RlbC5zdG9wKClcblxuICAgIEAkKFwiLnBsYXktY29udHJvbFwiKS5hdHRyIGRpc2FibGVkOiBcImRpc2FibGVkXCJcbiAgICBAbW9kZWwucGxheSgpXG4iLCJjbGFzcyBXZWJjYXN0ZXIuVmlldy5NaXhlciBleHRlbmRzIEJhY2tib25lLlZpZXdcbiAgcmVuZGVyOiAtPlxuICAgIEAkKFwiLnNsaWRlclwiKS5zbGlkZXJcbiAgICAgIHN0b3A6ID0+XG4gICAgICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXAgXCJoaWRlXCJcbiAgICAgIHNsaWRlOiAoZSwgdWkpID0+XG4gICAgICAgIEBtb2RlbC5zZXQgc2xpZGVyOiB1aS52YWx1ZVxuICAgICAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwIFwic2hvd1wiXG5cbiAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwXG4gICAgICB0aXRsZTogPT4gQG1vZGVsLmdldCBcInNsaWRlclwiXG4gICAgICB0cmlnZ2VyOiBcIlwiXG4gICAgICBhbmltYXRpb246IGZhbHNlXG4gICAgICBwbGFjZW1lbnQ6IFwiYm90dG9tXCJcblxuICAgIHRoaXNcbiIsImNsYXNzIFdlYmNhc3Rlci5WaWV3LlBsYXlsaXN0IGV4dGVuZHMgV2ViY2FzdGVyLlZpZXcuVHJhY2tcbiAgZXZlbnRzOlxuICAgIFwiY2xpY2sgLnBsYXktYXVkaW9cIiAgICAgIDogXCJvblBsYXlcIlxuICAgIFwiY2xpY2sgLnBhdXNlLWF1ZGlvXCIgICAgIDogXCJvblBhdXNlXCJcbiAgICBcImNsaWNrIC5wcmV2aW91c1wiICAgICAgICA6IFwib25QcmV2aW91c1wiXG4gICAgXCJjbGljayAubmV4dFwiICAgICAgICAgICAgOiBcIm9uTmV4dFwiXG4gICAgXCJjbGljayAuc3RvcFwiICAgICAgICAgICAgOiBcIm9uU3RvcFwiXG4gICAgXCJjbGljayAucHJvZ3Jlc3Mtc2Vla1wiICAgOiBcIm9uU2Vla1wiXG4gICAgXCJjbGljayAucGFzc1Rocm91Z2hcIiAgICAgOiBcIm9uUGFzc1Rocm91Z2hcIlxuICAgIFwiY2hhbmdlIC5maWxlc1wiICAgICAgICAgIDogXCJvbkZpbGVzXCJcbiAgICBcImNoYW5nZSAucGxheVRocm91Z2hcIiAgICA6IFwib25QbGF5VGhyb3VnaFwiXG4gICAgXCJjaGFuZ2UgLmxvb3BcIiAgICAgICAgICAgOiBcIm9uTG9vcFwiXG4gICAgXCJzdWJtaXRcIiAgICAgICAgICAgICAgICAgOiBcIm9uU3VibWl0XCJcblxuICBpbml0aWFsaXplOiAtPlxuICAgIHN1cGVyKClcblxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTpmaWxlSW5kZXhcIiwgPT5cbiAgICAgIEAkKFwiLnRyYWNrLXJvd1wiKS5yZW1vdmVDbGFzcyBcInN1Y2Nlc3NcIlxuICAgICAgQCQoXCIudHJhY2stcm93LSN7QG1vZGVsLmdldChcImZpbGVJbmRleFwiKX1cIikuYWRkQ2xhc3MgXCJzdWNjZXNzXCJcblxuICAgIEBtb2RlbC5vbiBcInBsYXlpbmdcIiwgPT5cbiAgICAgIEAkKFwiLnBsYXktY29udHJvbFwiKS5yZW1vdmVBdHRyIFwiZGlzYWJsZWRcIlxuICAgICAgQCQoXCIucGxheS1hdWRpb1wiKS5oaWRlKClcbiAgICAgIEAkKFwiLnBhdXNlLWF1ZGlvXCIpLnNob3coKVxuICAgICAgQCQoXCIudHJhY2stcG9zaXRpb24tdGV4dFwiKS5yZW1vdmVDbGFzcyhcImJsaW5rXCIpLnRleHQgXCJcIlxuICAgICAgQCQoXCIudm9sdW1lLWxlZnRcIikud2lkdGggXCIwJVwiXG4gICAgICBAJChcIi52b2x1bWUtcmlnaHRcIikud2lkdGggXCIwJVwiXG5cbiAgICAgIGlmIEBtb2RlbC5nZXQoXCJkdXJhdGlvblwiKVxuICAgICAgICBAJChcIi5wcm9ncmVzcy12b2x1bWVcIikuY3NzIFwiY3Vyc29yXCIsIFwicG9pbnRlclwiXG4gICAgICBlbHNlXG4gICAgICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uXCIpLmFkZENsYXNzKFwicHJvZ3Jlc3Mtc3RyaXBlZCBhY3RpdmVcIilcbiAgICAgICAgQHNldFRyYWNrUHJvZ3Jlc3MgMTAwXG5cbiAgICBAbW9kZWwub24gXCJwYXVzZWRcIiwgPT5cbiAgICAgIEAkKFwiLnBsYXktYXVkaW9cIikuc2hvdygpXG4gICAgICBAJChcIi5wYXVzZS1hdWRpb1wiKS5oaWRlKClcbiAgICAgIEAkKFwiLnZvbHVtZS1sZWZ0XCIpLndpZHRoIFwiMCVcIlxuICAgICAgQCQoXCIudm9sdW1lLXJpZ2h0XCIpLndpZHRoIFwiMCVcIlxuICAgICAgQCQoXCIudHJhY2stcG9zaXRpb24tdGV4dFwiKS5hZGRDbGFzcyBcImJsaW5rXCJcblxuICAgIEBtb2RlbC5vbiBcInN0b3BwZWRcIiwgPT5cbiAgICAgIEAkKFwiLnBsYXktYXVkaW9cIikuc2hvdygpXG4gICAgICBAJChcIi5wYXVzZS1hdWRpb1wiKS5oaWRlKClcbiAgICAgIEAkKFwiLnByb2dyZXNzLXZvbHVtZVwiKS5jc3MgXCJjdXJzb3JcIiwgXCJcIlxuICAgICAgQCQoXCIudHJhY2stcG9zaXRpb25cIikucmVtb3ZlQ2xhc3MoXCJwcm9ncmVzcy1zdHJpcGVkIGFjdGl2ZVwiKVxuICAgICAgQHNldFRyYWNrUHJvZ3Jlc3MgMFxuICAgICAgQCQoXCIudHJhY2stcG9zaXRpb24tdGV4dFwiKS5yZW1vdmVDbGFzcyhcImJsaW5rXCIpLnRleHQgXCJcIlxuICAgICAgQCQoXCIudm9sdW1lLWxlZnRcIikud2lkdGggXCIwJVwiXG4gICAgICBAJChcIi52b2x1bWUtcmlnaHRcIikud2lkdGggXCIwJVwiXG5cbiAgICBAbW9kZWwub24gXCJjaGFuZ2U6cG9zaXRpb25cIiwgPT5cbiAgICAgIHJldHVybiB1bmxlc3MgZHVyYXRpb24gPSBAbW9kZWwuZ2V0KFwiZHVyYXRpb25cIilcblxuICAgICAgcG9zaXRpb24gPSBwYXJzZUZsb2F0IEBtb2RlbC5nZXQoXCJwb3NpdGlvblwiKVxuXG4gICAgICBAc2V0VHJhY2tQcm9ncmVzcyAxMDAuMCpwb3NpdGlvbi9wYXJzZUZsb2F0KGR1cmF0aW9uKVxuXG4gICAgICBAJChcIi50cmFjay1wb3NpdGlvbi10ZXh0XCIpLlxuICAgICAgICB0ZXh0IFwiI3tXZWJjYXN0ZXIucHJldHRpZnlUaW1lKHBvc2l0aW9uKX0gLyAje1dlYmNhc3Rlci5wcmV0dGlmeVRpbWUoZHVyYXRpb24pfVwiXG5cbiAgcmVuZGVyOiAtPlxuICAgIEAkKFwiLnZvbHVtZS1zbGlkZXJcIikuc2xpZGVyXG4gICAgICBvcmllbnRhdGlvbjogXCJ2ZXJ0aWNhbFwiXG4gICAgICBtaW46IDBcbiAgICAgIG1heDogMTUwXG4gICAgICB2YWx1ZTogMTAwXG4gICAgICBzdG9wOiA9PlxuICAgICAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwIFwiaGlkZVwiXG4gICAgICBzbGlkZTogKGUsIHVpKSA9PlxuICAgICAgICBAbW9kZWwuc2V0IHRyYWNrR2FpbjogdWkudmFsdWVcbiAgICAgICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcCBcInNob3dcIlxuXG4gICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcFxuICAgICAgdGl0bGU6ID0+IEBtb2RlbC5nZXQgXCJ0cmFja0dhaW5cIlxuICAgICAgdHJpZ2dlcjogXCJcIlxuICAgICAgYW5pbWF0aW9uOiBmYWxzZVxuICAgICAgcGxhY2VtZW50OiBcImxlZnRcIlxuXG4gICAgZmlsZXMgPSBAbW9kZWwuZ2V0IFwiZmlsZXNcIlxuXG4gICAgQCQoXCIuZmlsZXMtdGFibGVcIikuZW1wdHkoKVxuXG4gICAgcmV0dXJuIHRoaXMgdW5sZXNzIGZpbGVzLmxlbmd0aCA+IDBcblxuICAgIF8uZWFjaCBmaWxlcywgKHtmaWxlLCBhdWRpbywgbWV0YWRhdGF9LCBpbmRleCkgPT5cbiAgICAgIGlmIGF1ZGlvPy5sZW5ndGggIT0gMFxuICAgICAgICB0aW1lID0gV2ViY2FzdGVyLnByZXR0aWZ5VGltZSBhdWRpby5sZW5ndGhcbiAgICAgIGVsc2VcbiAgICAgICAgdGltZSA9IFwiTi9BXCJcblxuICAgICAgaWYgQG1vZGVsLmdldChcImZpbGVJbmRleFwiKSA9PSBpbmRleFxuICAgICAgICBrbGFzcyA9IFwic3VjY2Vzc1wiXG4gICAgICBlbHNlXG4gICAgICAgIGtsYXNzID0gXCJcIlxuICAgICAgICBcbiAgICAgIEAkKFwiLmZpbGVzLXRhYmxlXCIpLmFwcGVuZCBcIlwiXCJcbiAgICAgICAgPHRyIGNsYXNzPSd0cmFjay1yb3cgdHJhY2stcm93LSN7aW5kZXh9ICN7a2xhc3N9Jz5cbiAgICAgICAgICA8dGQ+I3tpbmRleCsxfTwvdGQ+XG4gICAgICAgICAgPHRkPiN7bWV0YWRhdGE/LnRpdGxlIHx8IFwiVW5rbm93biBUaXRsZVwifTwvdGQ+XG4gICAgICAgICAgPHRkPiN7bWV0YWRhdGE/LmFydGlzdCB8fCBcIlVua25vd24gQXJ0aXN0XCJ9PC90ZD5cbiAgICAgICAgICA8dGQ+I3t0aW1lfTwvdGQ+XG4gICAgICAgIDwvdHI+XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIFwiXCJcIlxuXG4gICAgQCQoXCIucGxheWxpc3QtdGFibGVcIikuc2hvdygpXG5cbiAgICB0aGlzXG5cbiAgc2V0VHJhY2tQcm9ncmVzczogKHBlcmNlbnQpIC0+XG4gICAgQCQoXCIudHJhY2stcG9zaXRpb25cIikud2lkdGggXCIje3BlcmNlbnQqJChcIi5wcm9ncmVzcy12b2x1bWVcIikud2lkdGgoKS8xMDB9cHhcIlxuICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uLXRleHQsLnByb2dyZXNzLXNlZWtcIikud2lkdGggJChcIi5wcm9ncmVzcy12b2x1bWVcIikud2lkdGgoKVxuXG4gIHBsYXk6IChvcHRpb25zKSAtPlxuICAgIEBtb2RlbC5zdG9wKClcbiAgICByZXR1cm4gdW5sZXNzIEBmaWxlID0gQG1vZGVsLnNlbGVjdEZpbGUgb3B0aW9uc1xuXG4gICAgQCQoXCIucGxheS1jb250cm9sXCIpLmF0dHIgZGlzYWJsZWQ6IFwiZGlzYWJsZWRcIlxuICAgIEBtb2RlbC5wbGF5IEBmaWxlXG5cbiAgb25QbGF5OiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcbiAgICBpZiBAbW9kZWwuaXNQbGF5aW5nKClcbiAgICAgIEBtb2RlbC50b2dnbGVQYXVzZSgpXG4gICAgICByZXR1cm5cblxuICAgIEBwbGF5KClcblxuICBvblBhdXNlOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcbiAgICBAbW9kZWwudG9nZ2xlUGF1c2UoKVxuXG4gIG9uUHJldmlvdXM6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuICAgIHJldHVybiB1bmxlc3MgQG1vZGVsLmlzUGxheWluZygpP1xuXG4gICAgQHBsYXkgYmFja3dhcmQ6IHRydWVcblxuICBvbk5leHQ6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuICAgIHJldHVybiB1bmxlc3MgQG1vZGVsLmlzUGxheWluZygpXG5cbiAgICBAcGxheSgpXG5cbiAgb25TdG9wOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcblxuICAgIEAkKFwiLnRyYWNrLXJvd1wiKS5yZW1vdmVDbGFzcyBcInN1Y2Nlc3NcIlxuICAgIEBtb2RlbC5zdG9wKClcbiAgICBAZmlsZSA9IG51bGxcblxuICBvblNlZWs6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgQG1vZGVsLnNlZWsgKChlLnBhZ2VYIC0gJChlLnRhcmdldCkub2Zmc2V0KCkubGVmdCkgLyAkKGUudGFyZ2V0KS53aWR0aCgpKVxuXG4gIG9uRmlsZXM6IC0+XG4gICAgZmlsZXMgPSBAJChcIi5maWxlc1wiKVswXS5maWxlc1xuICAgIEAkKFwiLmZpbGVzXCIpLmF0dHIgZGlzYWJsZWQ6IFwiZGlzYWJsZWRcIlxuXG4gICAgQG1vZGVsLmFwcGVuZEZpbGVzIGZpbGVzLCA9PlxuICAgICAgQCQoXCIuZmlsZXNcIikucmVtb3ZlQXR0cihcImRpc2FibGVkXCIpLnZhbCBcIlwiXG4gICAgICBAcmVuZGVyKClcblxuICBvblBsYXlUaHJvdWdoOiAoZSkgLT5cbiAgICBAbW9kZWwuc2V0IHBsYXlUaHJvdWdoOiAkKGUudGFyZ2V0KS5pcyhcIjpjaGVja2VkXCIpXG5cbiAgb25Mb29wOiAoZSkgLT5cbiAgICBAbW9kZWwuc2V0IGxvb3A6ICQoZS50YXJnZXQpLmlzKFwiOmNoZWNrZWRcIilcbiIsImNsYXNzIFdlYmNhc3Rlci5WaWV3LlNldHRpbmdzIGV4dGVuZHMgQmFja2JvbmUuVmlld1xuICBldmVudHM6XG4gICAgXCJjaGFuZ2UgLnVyaVwiICAgICAgICAgICAgOiBcIm9uVXJpXCJcbiAgICBcImNoYW5nZSBpbnB1dC5lbmNvZGVyXCIgICA6IFwib25FbmNvZGVyXCJcbiAgICBcImNoYW5nZSBpbnB1dC5jaGFubmVsc1wiICA6IFwib25DaGFubmVsc1wiXG4gICAgXCJjaGFuZ2UgLnNhbXBsZXJhdGVcIiAgICAgOiBcIm9uU2FtcGxlcmF0ZVwiXG4gICAgXCJjaGFuZ2UgLmJpdHJhdGVcIiAgICAgICAgOiBcIm9uQml0cmF0ZVwiXG4gICAgXCJjaGFuZ2UgLmFzeW5jaHJvbm91c1wiICAgOiBcIm9uQXN5bmNocm9ub3VzXCJcbiAgICBcImNsaWNrIC5wYXNzVGhyb3VnaFwiICAgICA6IFwib25QYXNzVGhyb3VnaFwiXG4gICAgXCJjbGljayAuc3RhcnQtc3RyZWFtXCIgICAgOiBcIm9uU3RhcnRcIlxuICAgIFwiY2xpY2sgLnN0b3Atc3RyZWFtXCIgICAgIDogXCJvblN0b3BcIlxuICAgIFwiY2xpY2sgLnVwZGF0ZS1tZXRhZGF0YVwiIDogXCJvbk1ldGFkYXRhVXBkYXRlXCJcbiAgICBcInN1Ym1pdFwiICAgICAgICAgICAgICAgICA6IFwib25TdWJtaXRcIlxuXG4gIGluaXRpYWxpemU6ICh7QG5vZGV9KSAtPlxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTpwYXNzVGhyb3VnaFwiLCA9PlxuICAgICAgaWYgQG1vZGVsLmdldChcInBhc3NUaHJvdWdoXCIpXG4gICAgICAgIEAkKFwiLnBhc3NUaHJvdWdoXCIpLmFkZENsYXNzKFwiYnRuLWN1ZWRcIikucmVtb3ZlQ2xhc3MgXCJidG4taW5mb1wiXG4gICAgICBlbHNlXG4gICAgICAgIEAkKFwiLnBhc3NUaHJvdWdoXCIpLmFkZENsYXNzKFwiYnRuLWluZm9cIikucmVtb3ZlQ2xhc3MgXCJidG4tY3VlZFwiXG5cbiAgcmVuZGVyOiAtPlxuICAgIHNhbXBsZXJhdGUgPSBAbW9kZWwuZ2V0IFwic2FtcGxlcmF0ZVwiXG4gICAgQCQoXCIuc2FtcGxlcmF0ZVwiKS5lbXB0eSgpXG4gICAgXy5lYWNoIEBtb2RlbC5nZXQoXCJzYW1wbGVyYXRlc1wiKSwgKHJhdGUpID0+XG4gICAgICBzZWxlY3RlZCA9IGlmIHNhbXBsZXJhdGUgPT0gcmF0ZSB0aGVuIFwic2VsZWN0ZWRcIiBlbHNlIFwiXCJcbiAgICAgICQoXCI8b3B0aW9uIHZhbHVlPScje3JhdGV9JyAje3NlbGVjdGVkfT4je3JhdGV9PC9vcHRpb24+XCIpLlxuICAgICAgICBhcHBlbmRUbyBAJChcIi5zYW1wbGVyYXRlXCIpXG5cbiAgICBiaXRyYXRlID0gQG1vZGVsLmdldCBcImJpdHJhdGVcIlxuICAgIEAkKFwiLmJpdHJhdGVcIikuZW1wdHkoKVxuICAgIF8uZWFjaCBAbW9kZWwuZ2V0KFwiYml0cmF0ZXNcIiksIChyYXRlKSA9PlxuICAgICAgc2VsZWN0ZWQgPSBpZiBiaXRyYXRlID09IHJhdGUgdGhlbiBcInNlbGVjdGVkXCIgZWxzZSBcIlwiXG4gICAgICAkKFwiPG9wdGlvbiB2YWx1ZT0nI3tyYXRlfScgI3tzZWxlY3RlZH0+I3tyYXRlfTwvb3B0aW9uPlwiKS5cbiAgICAgICAgYXBwZW5kVG8gQCQoXCIuYml0cmF0ZVwiKVxuXG4gICAgdGhpc1xuXG4gIG9uVXJpOiAtPlxuICAgIEBtb2RlbC5zZXQgdXJpOiBAJChcIi51cmlcIikudmFsKClcblxuICBvbkVuY29kZXI6IChlKSAtPlxuICAgIEBtb2RlbC5zZXQgZW5jb2RlcjogJChlLnRhcmdldCkudmFsKClcblxuICBvbkNoYW5uZWxzOiAoZSkgLT5cbiAgICBAbW9kZWwuc2V0IGNoYW5uZWxzOiBwYXJzZUludCgkKGUudGFyZ2V0KS52YWwoKSlcblxuICBvblNhbXBsZXJhdGU6IChlKSAtPlxuICAgIEBtb2RlbC5zZXQgc2FtcGxlcmF0ZTogcGFyc2VJbnQoJChlLnRhcmdldCkudmFsKCkpXG5cbiAgb25CaXRyYXRlOiAoZSkgLT5cbiAgICBAbW9kZWwuc2V0IGJpdHJhdGU6IHBhcnNlSW50KCQoZS50YXJnZXQpLnZhbCgpKVxuXG4gIG9uQXN5bmNocm9ub3VzOiAoZSkgLT5cbiAgICBAbW9kZWwuc2V0IGFzeW5jaHJvbm91czogJChlLnRhcmdldCkuaXMoXCI6Y2hlY2tlZFwiKVxuXG4gIG9uUGFzc1Rocm91Z2g6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgQG1vZGVsLnRvZ2dsZVBhc3NUaHJvdWdoKClcblxuICBvblN0YXJ0OiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcblxuICAgIEAkKFwiLnN0b3Atc3RyZWFtXCIpLnNob3coKVxuICAgIEAkKFwiLnN0YXJ0LXN0cmVhbVwiKS5oaWRlKClcbiAgICBAJChcImlucHV0LCBzZWxlY3RcIikuYXR0ciBkaXNhYmxlZDogXCJkaXNhYmxlZFwiXG4gICAgQCQoXCIubWFudWFsLW1ldGFkYXRhLCAudXBkYXRlLW1ldGFkYXRhXCIpLnJlbW92ZUF0dHIgXCJkaXNhYmxlZFwiXG5cbiAgICBAbm9kZS5zdGFydFN0cmVhbSgpXG5cbiAgb25TdG9wOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcblxuICAgIEAkKFwiLnN0b3Atc3RyZWFtXCIpLmhpZGUoKVxuICAgIEAkKFwiLnN0YXJ0LXN0cmVhbVwiKS5zaG93KClcbiAgICBAJChcImlucHV0LCBzZWxlY3RcIikucmVtb3ZlQXR0ciBcImRpc2FibGVkXCJcbiAgICBAJChcIi5tYW51YWwtbWV0YWRhdGEsIC51cGRhdGUtbWV0YWRhdGFcIikuYXR0ciBkaXNhYmxlZDogXCJkaXNhYmxlZFwiXG5cbiAgICBAbm9kZS5zdG9wU3RyZWFtKClcblxuICBvbk1ldGFkYXRhVXBkYXRlOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcblxuICAgIHRpdGxlID0gQCQoXCIubWFudWFsLW1ldGFkYXRhLmFydGlzdFwiKS52YWwoKVxuICAgIGFydGlzdCA9IEAkKFwiLm1hbnVhbC1tZXRhZGF0YS50aXRsZVwiKS52YWwoKVxuXG4gICAgcmV0dXJuIHVubGVzcyBhcnRpc3QgIT0gXCJcIiB8fCB0aXRsZSAhPSBcIlwiXG5cbiAgICBAbm9kZS5zZW5kTWV0YWRhdGFcbiAgICAgIGFydGlzdDogYXJ0aXN0XG4gICAgICB0aXRsZTogIHRpdGxlXG5cbiAgICBAJChcIi5tZXRhZGF0YS11cGRhdGVkXCIpLnNob3cgNDAwLCA9PlxuICAgICBjYiA9ID0+XG4gICAgICAgQCQoXCIubWV0YWRhdGEtdXBkYXRlZFwiKS5oaWRlIDQwMFxuXG4gICAgIHNldFRpbWVvdXQgY2IsIDIwMDBcblxuICBvblN1Ym1pdDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG4iLCIkIC0+XG4gIFdlYmNhc3Rlci5taXhlciA9IG5ldyBXZWJjYXN0ZXIuTW9kZWwuTWl4ZXJcbiAgICBzbGlkZXI6IDBcblxuICBXZWJjYXN0ZXIuc2V0dGluZ3MgPSBuZXcgV2ViY2FzdGVyLk1vZGVsLlNldHRpbmdzKHtcbiAgICB1cmk6ICAgICAgICAgIFwid3M6Ly9zb3VyY2U6aGFja21lQGxvY2FsaG9zdDo4MDgwL21vdW50XCJcbiAgICBiaXRyYXRlOiAgICAgIDEyOFxuICAgIGJpdHJhdGVzOiAgICAgWyA4LCAxNiwgMjQsIDMyLCA0MCwgNDgsIDU2LFxuICAgICAgICAgICAgICAgICAgICA2NCwgODAsIDk2LCAxMTIsIDEyOCwgMTQ0LFxuICAgICAgICAgICAgICAgICAgICAxNjAsIDE5MiwgMjI0LCAyNTYsIDMyMCBdXG4gICAgc2FtcGxlcmF0ZTogICA0NDEwMFxuICAgIHNhbXBsZXJhdGVzOiAgWyA4MDAwLCAxMTAyNSwgMTIwMDAsIDE2MDAwLFxuICAgICAgICAgICAgICAgICAgICAyMjA1MCwgMjQwMDAsIDMyMDAwLCA0NDEwMCwgNDgwMDAgXVxuICAgIGNoYW5uZWxzOiAgICAgMlxuICAgIGVuY29kZXI6ICAgICAgXCJtcDNcIlxuICAgIGFzeW5jaHJvbm91czogdHJ1ZVxuICAgIHBhc3NUaHJvdWdoOiAgZmFsc2VcbiAgfSwge1xuICAgIG1peGVyOiBXZWJjYXN0ZXIubWl4ZXJcbiAgfSlcblxuICBXZWJjYXN0ZXIubm9kZSA9IG5ldyBXZWJjYXN0ZXIuTm9kZVxuICAgIG1vZGVsOiBXZWJjYXN0ZXIuc2V0dGluZ3NcblxuICBfLmV4dGVuZCBXZWJjYXN0ZXIsXG4gICAgdmlld3M6XG4gICAgICBzZXR0aW5ncyA6IG5ldyBXZWJjYXN0ZXIuVmlldy5TZXR0aW5nc1xuICAgICAgICBtb2RlbCA6IFdlYmNhc3Rlci5zZXR0aW5nc1xuICAgICAgICBub2RlICA6IFdlYmNhc3Rlci5ub2RlXG4gICAgICAgIGVsICAgIDogJChcImRpdi5zZXR0aW5nc1wiKVxuXG4gICAgICBtaXhlcjogbmV3IFdlYmNhc3Rlci5WaWV3Lk1peGVyXG4gICAgICAgIG1vZGVsIDogV2ViY2FzdGVyLm1peGVyXG4gICAgICAgIGVsICAgIDogJChcImRpdi5taXhlclwiKVxuXG4gICAgICBtaWNyb3Bob25lOiBuZXcgV2ViY2FzdGVyLlZpZXcuTWljcm9waG9uZVxuICAgICAgICBtb2RlbDogbmV3IFdlYmNhc3Rlci5Nb2RlbC5NaWNyb3Bob25lKHtcbiAgICAgICAgICB0cmFja0dhaW4gICA6IDEwMFxuICAgICAgICAgIHBhc3NUaHJvdWdoIDogZmFsc2VcbiAgICAgICAgfSwge1xuICAgICAgICAgIG1peGVyOiBXZWJjYXN0ZXIubWl4ZXJcbiAgICAgICAgICBub2RlOiAgV2ViY2FzdGVyLm5vZGVcbiAgICAgICAgfSlcbiAgICAgICAgZWw6ICQoXCJkaXYubWljcm9waG9uZVwiKVxuXG4gICAgICBwbGF5bGlzdExlZnQgOiBuZXcgV2ViY2FzdGVyLlZpZXcuUGxheWxpc3RcbiAgICAgICAgbW9kZWwgOiBuZXcgV2ViY2FzdGVyLk1vZGVsLlBsYXlsaXN0KHtcbiAgICAgICAgICBzaWRlICAgICAgICA6IFwibGVmdFwiXG4gICAgICAgICAgZmlsZXMgICAgICAgOiBbXVxuICAgICAgICAgIGZpbGVJbmRleCAgIDogLTFcbiAgICAgICAgICB2b2x1bWVMZWZ0ICA6IDBcbiAgICAgICAgICB2b2x1bWVSaWdodCA6IDBcbiAgICAgICAgICB0cmFja0dhaW4gICA6IDEwMFxuICAgICAgICAgIHBhc3NUaHJvdWdoIDogZmFsc2VcbiAgICAgICAgICBwbGF5VGhyb3VnaCA6IHRydWVcbiAgICAgICAgICBwb3NpdGlvbiAgICA6IDAuMFxuICAgICAgICAgIGxvb3AgICAgICAgIDogZmFsc2VcbiAgICAgICAgfSwge1xuICAgICAgICAgIG1peGVyIDogV2ViY2FzdGVyLm1peGVyXG4gICAgICAgICAgbm9kZSAgOiBXZWJjYXN0ZXIubm9kZVxuICAgICAgICB9KVxuICAgICAgICBlbCA6ICQoXCJkaXYucGxheWxpc3QtbGVmdFwiKVxuXG4gICAgICBwbGF5bGlzdFJpZ2h0IDogbmV3IFdlYmNhc3Rlci5WaWV3LlBsYXlsaXN0XG4gICAgICAgIG1vZGVsIDogbmV3IFdlYmNhc3Rlci5Nb2RlbC5QbGF5bGlzdCh7XG4gICAgICAgICAgc2lkZSAgICAgICAgOiBcInJpZ2h0XCJcbiAgICAgICAgICBmaWxlcyAgICAgICA6IFtdXG4gICAgICAgICAgZmlsZUluZGV4ICAgOiAtMVxuICAgICAgICAgIHZvbHVtZUxlZnQgIDogMFxuICAgICAgICAgIHZvbHVtZVJpZ2h0IDogMFxuICAgICAgICAgIHRyYWNrR2FpbiAgIDogMTAwXG4gICAgICAgICAgcGFzc1Rocm91Z2ggOiBmYWxzZVxuICAgICAgICAgIHBsYXlUaHJvdWdoIDogdHJ1ZVxuICAgICAgICAgIHBvc2l0aW9uICAgIDogMC4wXG4gICAgICAgICAgbG9vcCAgICAgICAgOiBmYWxzZVxuICAgICAgICB9LCB7XG4gICAgICAgICAgbWl4ZXIgOiBXZWJjYXN0ZXIubWl4ZXJcbiAgICAgICAgICBub2RlICA6IFdlYmNhc3Rlci5ub2RlXG4gICAgICAgIH0pXG4gICAgICAgIGVsIDogJChcImRpdi5wbGF5bGlzdC1yaWdodFwiKVxuXG5cbiAgXy5pbnZva2UgV2ViY2FzdGVyLnZpZXdzLCBcInJlbmRlclwiXG4iXX0=
