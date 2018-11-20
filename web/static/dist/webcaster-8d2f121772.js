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

//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJzb3VyY2VzIjpbImNvbXBhdC5jb2ZmZWUiLCJpbml0LmNvZmZlZSIsIm5vZGUuY29mZmVlIiwid2ViY2FzdGVyLmNvZmZlZSIsInZpZXdzL21pY3JvcGhvbmUuY29mZmVlIiwidmlld3MvbWl4ZXIuY29mZmVlIiwidmlld3MvcGxheWxpc3QuY29mZmVlIiwidmlld3Mvc2V0dGluZ3MuY29mZmVlIiwidmlld3MvdHJhY2suY29mZmVlIiwibW9kZWxzL21pY3JvcGhvbmUuY29mZmVlIiwibW9kZWxzL21peGVyLmNvZmZlZSIsIm1vZGVscy9wbGF5bGlzdC5jb2ZmZWUiLCJtb2RlbHMvc2V0dGluZ3MuY29mZmVlIiwibW9kZWxzL3RyYWNrLmNvZmZlZSJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiQUFBQTtBQUFBLE1BQUEsSUFBQSxFQUFBOztFQUFBLFNBQVMsQ0FBQyxpQkFBVixTQUFTLENBQUMsZUFBaUIsQ0FBQTs7VUFFM0IsU0FBUyxDQUFDLGFBQVksQ0FBQyxxQkFBRCxDQUFDLGVBQWlCLFFBQUEsQ0FBQyxXQUFELENBQUE7QUFDdEMsUUFBQTtJQUFBLEVBQUEsR0FBSyxTQUFTLENBQUMsWUFBVixJQUEwQixTQUFTLENBQUMsa0JBQXBDLElBQTBELFNBQVMsQ0FBQyxlQUFwRSxJQUF1RixTQUFTLENBQUM7SUFFdEcsSUFBTyxVQUFQO0FBQ0UsYUFBTyxPQUFPLENBQUMsTUFBUixDQUFlLElBQUksS0FBSixDQUFVLGlEQUFWLENBQWYsRUFEVDs7V0FHQSxJQUFJLE9BQUosQ0FBWSxRQUFBLENBQUMsT0FBRCxFQUFVLE1BQVYsQ0FBQTthQUNWLEVBQUUsQ0FBQyxJQUFILENBQVEsU0FBUixFQUFtQixXQUFuQixFQUFnQyxPQUFoQyxFQUF5QyxNQUF6QztJQURVLENBQVo7RUFOc0M7O1dBU3hDLFNBQVMsQ0FBQyxhQUFZLENBQUMsMEJBQUQsQ0FBQyxtQkFBcUIsUUFBQSxDQUFBLENBQUE7V0FDMUMsT0FBTyxDQUFDLE1BQVIsQ0FBZSxJQUFJLEtBQUosQ0FBVSxxREFBVixDQUFmO0VBRDBDO0FBWDVDOzs7QUNBQTtFQUFBLENBQUEsQ0FBRSxRQUFBLENBQUEsQ0FBQTtJQUNBLFNBQVMsQ0FBQyxLQUFWLEdBQWtCLElBQUksU0FBUyxDQUFDLEtBQUssQ0FBQyxLQUFwQixDQUNoQjtNQUFBLE1BQUEsRUFBUTtJQUFSLENBRGdCO0lBR2xCLFNBQVMsQ0FBQyxRQUFWLEdBQXFCLElBQUksU0FBUyxDQUFDLEtBQUssQ0FBQyxRQUFwQixDQUE2QjtNQUNoRCxHQUFBLEVBQWMseUNBRGtDO01BRWhELE9BQUEsRUFBYyxHQUZrQztNQUdoRCxRQUFBLEVBQWMsQ0FBRSxDQUFGLEVBQUssRUFBTCxFQUFTLEVBQVQsRUFBYSxFQUFiLEVBQWlCLEVBQWpCLEVBQXFCLEVBQXJCLEVBQXlCLEVBQXpCLEVBQ0UsRUFERixFQUNNLEVBRE4sRUFDVSxFQURWLEVBQ2MsR0FEZCxFQUNtQixHQURuQixFQUN3QixHQUR4QixFQUVFLEdBRkYsRUFFTyxHQUZQLEVBRVksR0FGWixFQUVpQixHQUZqQixFQUVzQixHQUZ0QixDQUhrQztNQU1oRCxVQUFBLEVBQWMsS0FOa0M7TUFPaEQsV0FBQSxFQUFjLENBQUUsSUFBRixFQUFRLEtBQVIsRUFBZSxLQUFmLEVBQXNCLEtBQXRCLEVBQ0UsS0FERixFQUNTLEtBRFQsRUFDZ0IsS0FEaEIsRUFDdUIsS0FEdkIsRUFDOEIsS0FEOUIsQ0FQa0M7TUFTaEQsUUFBQSxFQUFjLENBVGtDO01BVWhELE9BQUEsRUFBYyxLQVZrQztNQVdoRCxZQUFBLEVBQWMsSUFYa0M7TUFZaEQsV0FBQSxFQUFjO0lBWmtDLENBQTdCLEVBYWxCO01BQ0QsS0FBQSxFQUFPLFNBQVMsQ0FBQztJQURoQixDQWJrQjtJQWlCckIsU0FBUyxDQUFDLElBQVYsR0FBaUIsSUFBSSxTQUFTLENBQUMsSUFBZCxDQUNmO01BQUEsS0FBQSxFQUFPLFNBQVMsQ0FBQztJQUFqQixDQURlO0lBR2pCLENBQUMsQ0FBQyxNQUFGLENBQVMsU0FBVCxFQUNFO01BQUEsS0FBQSxFQUNFO1FBQUEsUUFBQSxFQUFXLElBQUksU0FBUyxDQUFDLElBQUksQ0FBQyxRQUFuQixDQUNUO1VBQUEsS0FBQSxFQUFRLFNBQVMsQ0FBQyxRQUFsQjtVQUNBLElBQUEsRUFBUSxTQUFTLENBQUMsSUFEbEI7VUFFQSxFQUFBLEVBQVEsQ0FBQSxDQUFFLGNBQUY7UUFGUixDQURTLENBQVg7UUFLQSxLQUFBLEVBQU8sSUFBSSxTQUFTLENBQUMsSUFBSSxDQUFDLEtBQW5CLENBQ0w7VUFBQSxLQUFBLEVBQVEsU0FBUyxDQUFDLEtBQWxCO1VBQ0EsRUFBQSxFQUFRLENBQUEsQ0FBRSxXQUFGO1FBRFIsQ0FESyxDQUxQO1FBU0EsVUFBQSxFQUFZLElBQUksU0FBUyxDQUFDLElBQUksQ0FBQyxVQUFuQixDQUNWO1VBQUEsS0FBQSxFQUFPLElBQUksU0FBUyxDQUFDLEtBQUssQ0FBQyxVQUFwQixDQUErQjtZQUNwQyxTQUFBLEVBQWMsR0FEc0I7WUFFcEMsV0FBQSxFQUFjO1VBRnNCLENBQS9CLEVBR0o7WUFDRCxLQUFBLEVBQU8sU0FBUyxDQUFDLEtBRGhCO1lBRUQsSUFBQSxFQUFPLFNBQVMsQ0FBQztVQUZoQixDQUhJLENBQVA7VUFPQSxFQUFBLEVBQUksQ0FBQSxDQUFFLGdCQUFGO1FBUEosQ0FEVSxDQVRaO1FBbUJBLFlBQUEsRUFBZSxJQUFJLFNBQVMsQ0FBQyxJQUFJLENBQUMsUUFBbkIsQ0FDYjtVQUFBLEtBQUEsRUFBUSxJQUFJLFNBQVMsQ0FBQyxLQUFLLENBQUMsUUFBcEIsQ0FBNkI7WUFDbkMsSUFBQSxFQUFjLE1BRHFCO1lBRW5DLEtBQUEsRUFBYyxFQUZxQjtZQUduQyxTQUFBLEVBQWMsQ0FBQyxDQUhvQjtZQUluQyxVQUFBLEVBQWMsQ0FKcUI7WUFLbkMsV0FBQSxFQUFjLENBTHFCO1lBTW5DLFNBQUEsRUFBYyxHQU5xQjtZQU9uQyxXQUFBLEVBQWMsS0FQcUI7WUFRbkMsV0FBQSxFQUFjLElBUnFCO1lBU25DLFFBQUEsRUFBYyxHQVRxQjtZQVVuQyxJQUFBLEVBQWM7VUFWcUIsQ0FBN0IsRUFXTDtZQUNELEtBQUEsRUFBUSxTQUFTLENBQUMsS0FEakI7WUFFRCxJQUFBLEVBQVEsU0FBUyxDQUFDO1VBRmpCLENBWEssQ0FBUjtVQWVBLEVBQUEsRUFBSyxDQUFBLENBQUUsbUJBQUY7UUFmTCxDQURhLENBbkJmO1FBcUNBLGFBQUEsRUFBZ0IsSUFBSSxTQUFTLENBQUMsSUFBSSxDQUFDLFFBQW5CLENBQ2Q7VUFBQSxLQUFBLEVBQVEsSUFBSSxTQUFTLENBQUMsS0FBSyxDQUFDLFFBQXBCLENBQTZCO1lBQ25DLElBQUEsRUFBYyxPQURxQjtZQUVuQyxLQUFBLEVBQWMsRUFGcUI7WUFHbkMsU0FBQSxFQUFjLENBQUMsQ0FIb0I7WUFJbkMsVUFBQSxFQUFjLENBSnFCO1lBS25DLFdBQUEsRUFBYyxDQUxxQjtZQU1uQyxTQUFBLEVBQWMsR0FOcUI7WUFPbkMsV0FBQSxFQUFjLEtBUHFCO1lBUW5DLFdBQUEsRUFBYyxJQVJxQjtZQVNuQyxRQUFBLEVBQWMsR0FUcUI7WUFVbkMsSUFBQSxFQUFjO1VBVnFCLENBQTdCLEVBV0w7WUFDRCxLQUFBLEVBQVEsU0FBUyxDQUFDLEtBRGpCO1lBRUQsSUFBQSxFQUFRLFNBQVMsQ0FBQztVQUZqQixDQVhLLENBQVI7VUFlQSxFQUFBLEVBQUssQ0FBQSxDQUFFLG9CQUFGO1FBZkwsQ0FEYztNQXJDaEI7SUFERixDQURGO1dBMERBLENBQUMsQ0FBQyxNQUFGLENBQVMsU0FBUyxDQUFDLEtBQW5CLEVBQTBCLFFBQTFCO0VBbEZBLENBQUY7QUFBQTs7O0FDQUE7RUFBTSxTQUFTLENBQUM7OztJQUFoQixNQUFBLEtBQUE7TUFLRSxXQUFhLENBQUM7VUFBRTtRQUFGLENBQUQsQ0FBQTtRQUFFLElBQUMsQ0FBQTtRQUNkLElBQUcsT0FBTyxrQkFBUCxLQUE2QixXQUFoQztVQUNFLElBQUMsQ0FBQSxPQUFELEdBQVcsSUFBSSxtQkFEakI7U0FBQSxNQUFBO1VBR0UsSUFBQyxDQUFBLE9BQUQsR0FBVyxJQUFJLGFBSGpCOztRQUtBLElBQUMsQ0FBQSxPQUFELEdBQVcsSUFBQyxDQUFBLE9BQU8sQ0FBQyxtQkFBVCxDQUE2QixJQUE3QixFQUFtQyxlQUFuQztRQUVYLElBQUMsQ0FBQSxPQUFELENBQUE7UUFFQSxJQUFDLENBQUEsS0FBSyxDQUFDLEVBQVAsQ0FBVSxvQkFBVixFQUFnQyxDQUFBLENBQUEsR0FBQTtpQkFDOUIsSUFBQyxDQUFBLE9BQU8sQ0FBQyxjQUFULENBQXdCLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLGFBQVgsQ0FBeEI7UUFEOEIsQ0FBaEM7UUFHQSxJQUFDLENBQUEsS0FBSyxDQUFDLEVBQVAsQ0FBVSxpQkFBVixFQUE2QixDQUFBLENBQUEsR0FBQTtpQkFDM0IsSUFBQyxDQUFBLFNBQUQsQ0FBQTtRQUQyQixDQUE3QjtNQWJXOztNQWdCYixPQUFTLENBQUEsQ0FBQTtRQUNQLElBQUcsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsVUFBWCxDQUFBLEtBQTBCLENBQTdCO1VBQ0UsSUFBQyxDQUFBLFdBQUQsSUFBQyxDQUFBLFNBQVcsSUFBQyxDQUFBLE9BQU8sQ0FBQyxtQkFBVCxDQUE2QixJQUFDLENBQUEsZUFBOUI7VUFDWixJQUFDLENBQUEsTUFBTSxDQUFDLE9BQVIsQ0FBZ0IsSUFBQyxDQUFBLE9BQU8sQ0FBQyxXQUF6QjtpQkFDQSxJQUFDLENBQUEsT0FBTyxDQUFDLE9BQVQsQ0FBaUIsSUFBQyxDQUFBLE1BQWxCLEVBSEY7U0FBQSxNQUFBO2lCQUtFLElBQUMsQ0FBQSxPQUFPLENBQUMsT0FBVCxDQUFpQixJQUFDLENBQUEsT0FBTyxDQUFDLFdBQTFCLEVBTEY7O01BRE87O01BUVQsVUFBWSxDQUFBLENBQUE7QUFDVixZQUFBO1FBQUEsSUFBQyxDQUFBLE9BQU8sQ0FBQyxVQUFULENBQUE7Z0RBQ08sQ0FBRSxVQUFULENBQUE7TUFGVTs7TUFJWixTQUFXLENBQUEsQ0FBQTtRQUNULElBQUMsQ0FBQSxVQUFELENBQUE7ZUFDQSxJQUFDLENBQUEsT0FBRCxDQUFBO01BRlM7O01BSVgsV0FBYSxDQUFBLENBQUE7QUFDWCxZQUFBO0FBQUEsZ0JBQU8sSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsU0FBWCxDQUFQO0FBQUEsZUFDTyxLQURQO1lBRUksT0FBQSxHQUFVLE9BQU8sQ0FBQyxPQUFPLENBQUM7QUFEdkI7QUFEUCxlQUdPLEtBSFA7WUFJSSxPQUFBLEdBQVUsT0FBTyxDQUFDLE9BQU8sQ0FBQztBQUo5QjtRQU1BLElBQUMsQ0FBQSxPQUFELEdBQVcsSUFBSSxPQUFKLENBQ1Q7VUFBQSxRQUFBLEVBQWEsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsVUFBWCxDQUFiO1VBQ0EsVUFBQSxFQUFhLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLFlBQVgsQ0FEYjtVQUVBLE9BQUEsRUFBYSxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxTQUFYO1FBRmIsQ0FEUztRQUtYLElBQUcsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsWUFBWCxDQUFBLEtBQTRCLElBQUMsQ0FBQSxPQUFPLENBQUMsVUFBeEM7VUFDRSxJQUFDLENBQUEsT0FBRCxHQUFXLElBQUksT0FBTyxDQUFDLE9BQU8sQ0FBQyxRQUFwQixDQUNUO1lBQUEsT0FBQSxFQUFhLElBQUMsQ0FBQSxPQUFkO1lBQ0EsSUFBQSxFQUFhLFVBQVUsQ0FBQyxNQUR4QjtZQUVBLFVBQUEsRUFBYSxJQUFDLENBQUEsT0FBTyxDQUFDO1VBRnRCLENBRFMsRUFEYjs7UUFNQSxJQUFHLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLGNBQVgsQ0FBSDtVQUNFLElBQUMsQ0FBQSxPQUFELEdBQVcsSUFBSSxPQUFPLENBQUMsT0FBTyxDQUFDLFlBQXBCLENBQ1Q7WUFBQSxPQUFBLEVBQVUsSUFBQyxDQUFBLE9BQVg7WUFDQSxPQUFBLEVBQVMsQ0FDUCw4RUFETyxFQUVQLGlFQUZPLEVBR1AsaUVBSE87VUFEVCxDQURTLEVBRGI7O2VBU0EsSUFBQyxDQUFBLE9BQU8sQ0FBQyxhQUFULENBQXVCLElBQUMsQ0FBQSxPQUF4QixFQUFpQyxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxLQUFYLENBQWpDO01BM0JXOztNQTZCYixVQUFZLENBQUEsQ0FBQTtlQUNWLElBQUMsQ0FBQSxPQUFPLENBQUMsS0FBVCxDQUFBO01BRFU7O01BR1osaUJBQW1CLENBQUMsQ0FBQyxJQUFELEVBQU8sS0FBUCxDQUFELEVBQWdCLEtBQWhCLEVBQXVCLEVBQXZCLENBQUE7QUFDakIsWUFBQSxFQUFBLEVBQUE7UUFBQSxFQUFBLEdBQUssSUFBSSxLQUFKLENBQVUsR0FBRyxDQUFDLGVBQUosQ0FBb0IsSUFBcEIsQ0FBVjtRQUNMLEVBQUUsQ0FBQyxRQUFILEdBQWM7UUFDZCxFQUFFLENBQUMsUUFBSCxHQUFjO1FBQ2QsRUFBRSxDQUFDLElBQUgsR0FBYztRQUVkLEVBQUUsQ0FBQyxnQkFBSCxDQUFvQixPQUFwQixFQUE2QixDQUFBLENBQUEsR0FBQTtpQkFDM0IsS0FBSyxDQUFDLEtBQU4sQ0FBQTtRQUQyQixDQUE3QjtRQUdBLE1BQUEsR0FBUztlQUVULEVBQUUsQ0FBQyxnQkFBSCxDQUFvQixTQUFwQixFQUErQixDQUFBLENBQUEsR0FBQTtVQUM3QixJQUFVLGNBQVY7QUFBQSxtQkFBQTs7VUFFQSxNQUFBLEdBQVMsSUFBQyxDQUFBLE9BQU8sQ0FBQyx3QkFBVCxDQUFrQyxFQUFsQztVQUVULE1BQU0sQ0FBQyxJQUFQLEdBQWMsUUFBQSxDQUFBLENBQUE7bUJBQ1osRUFBRSxDQUFDLElBQUgsQ0FBQTtVQURZO1VBR2QsTUFBTSxDQUFDLFFBQVAsR0FBa0IsUUFBQSxDQUFBLENBQUE7bUJBQ2hCLEVBQUUsQ0FBQztVQURhO1VBR2xCLE1BQU0sQ0FBQyxRQUFQLEdBQWtCLFFBQUEsQ0FBQSxDQUFBO21CQUNoQixFQUFFLENBQUM7VUFEYTtVQUdsQixNQUFNLENBQUMsTUFBUCxHQUFnQixRQUFBLENBQUEsQ0FBQTttQkFDZCxFQUFFLENBQUM7VUFEVztVQUdoQixNQUFNLENBQUMsSUFBUCxHQUFjLFFBQUEsQ0FBQSxDQUFBO1lBQ1osRUFBRSxDQUFDLEtBQUgsQ0FBQTttQkFDQSxFQUFFLENBQUMsTUFBSCxDQUFBO1VBRlk7VUFJZCxNQUFNLENBQUMsS0FBUCxHQUFlLFFBQUEsQ0FBQSxDQUFBO21CQUNiLEVBQUUsQ0FBQyxLQUFILENBQUE7VUFEYTtVQUdmLE1BQU0sQ0FBQyxJQUFQLEdBQWMsUUFBQSxDQUFDLE9BQUQsQ0FBQTtBQUNaLGdCQUFBO1lBQUEsSUFBQSxHQUFPLE9BQUEsR0FBUSxVQUFBLENBQVcsS0FBSyxDQUFDLE1BQWpCO1lBRWYsRUFBRSxDQUFDLFdBQUgsR0FBaUI7bUJBQ2pCO1VBSlk7aUJBTWQsRUFBQSxDQUFHLE1BQUg7UUE5QjZCLENBQS9CO01BWGlCOztNQTJDbkIsZ0JBQWtCLENBQUMsSUFBRCxFQUFPLEtBQVAsRUFBYyxFQUFkLENBQUE7QUFDaEIsWUFBQTs7YUFBTyxDQUFFLFVBQVQsQ0FBQTs7ZUFFQSxJQUFDLENBQUEsaUJBQUQsQ0FBbUIsSUFBbkIsRUFBeUIsS0FBekIsRUFBZ0MsRUFBaEM7TUFIZ0I7O01BS2xCLHNCQUF3QixDQUFDLFdBQUQsRUFBYyxFQUFkLENBQUE7ZUFDdEIsU0FBUyxDQUFDLFlBQVksQ0FBQyxZQUF2QixDQUFvQyxXQUFwQyxDQUFnRCxDQUFDLElBQWpELENBQXNELENBQUMsTUFBRCxDQUFBLEdBQUE7QUFDcEQsY0FBQTtVQUFBLE1BQUEsR0FBUyxJQUFDLENBQUEsT0FBTyxDQUFDLHVCQUFULENBQWlDLE1BQWpDO1VBRVQsTUFBTSxDQUFDLElBQVAsR0FBYyxRQUFBLENBQUEsQ0FBQTtBQUNaLGdCQUFBO2dFQUF5QixDQUFBLENBQUEsQ0FBRSxDQUFDLElBQTVCLENBQUE7VUFEWTtpQkFHZCxFQUFBLENBQUcsTUFBSDtRQU5vRCxDQUF0RDtNQURzQjs7TUFTeEIsWUFBYyxDQUFDLElBQUQsQ0FBQTtlQUNaLElBQUMsQ0FBQSxPQUFPLENBQUMsWUFBVCxDQUFzQixJQUF0QjtNQURZOztNQUdkLEtBQU8sQ0FBQyxFQUFELENBQUE7ZUFDTCxJQUFDLENBQUEsT0FBTyxDQUFDLEtBQVQsQ0FBZSxFQUFmO01BREs7O0lBaklUOztJQUNFLENBQUMsQ0FBQyxNQUFGLENBQVMsSUFBQyxDQUFBLFNBQVYsRUFBcUIsUUFBUSxDQUFDLE1BQTlCOztJQUVBLGVBQUEsR0FBa0I7Ozs7O0FBSHBCOzs7QUNBQTtBQUFBLE1BQUE7O0VBQUEsTUFBTSxDQUFDLFNBQVAsR0FBbUIsU0FBQSxHQUNqQjtJQUFBLElBQUEsRUFBTSxDQUFBLENBQU47SUFDQSxLQUFBLEVBQU8sQ0FBQSxDQURQO0lBRUEsTUFBQSxFQUFRLENBQUEsQ0FGUjtJQUlBLFlBQUEsRUFBYyxRQUFBLENBQUMsSUFBRCxDQUFBO0FBQ1osVUFBQSxLQUFBLEVBQUEsT0FBQSxFQUFBLE1BQUEsRUFBQTtNQUFBLEtBQUEsR0FBVSxRQUFBLENBQVMsSUFBQSxHQUFPLElBQWhCO01BQ1YsSUFBQSxJQUFVO01BQ1YsT0FBQSxHQUFVLFFBQUEsQ0FBUyxJQUFBLEdBQU8sRUFBaEI7TUFDVixPQUFBLEdBQVUsUUFBQSxDQUFTLElBQUEsR0FBTyxFQUFoQjtNQUVWLElBQTJCLE9BQUEsR0FBVSxFQUFyQztRQUFBLE9BQUEsR0FBVSxDQUFBLENBQUEsQ0FBQSxDQUFJLE9BQUosQ0FBQSxFQUFWOztNQUNBLElBQTJCLE9BQUEsR0FBVSxFQUFyQztRQUFBLE9BQUEsR0FBVSxDQUFBLENBQUEsQ0FBQSxDQUFJLE9BQUosQ0FBQSxFQUFWOztNQUVBLE1BQUEsR0FBUyxDQUFBLENBQUEsQ0FBRyxPQUFILENBQVcsQ0FBWCxDQUFBLENBQWMsT0FBZCxDQUFBO01BQ1QsSUFBaUMsS0FBQSxHQUFRLENBQXpDO1FBQUEsTUFBQSxHQUFTLENBQUEsQ0FBQSxDQUFHLEtBQUgsQ0FBUyxDQUFULENBQUEsQ0FBWSxNQUFaLENBQUEsRUFBVDs7YUFFQTtJQVpZO0VBSmQ7QUFERjs7O0FDQUE7RUFBTSxTQUFTLENBQUMsSUFBSSxDQUFDO0lBQXJCLE1BQUEsV0FBQSxRQUF3QyxTQUFTLENBQUMsSUFBSSxDQUFDLE1BQXZEO01BTUUsVUFBWSxDQUFBLENBQUE7YUFBWixDQUFBLFVBQ0UsQ0FBQTtRQUVBLElBQUMsQ0FBQSxLQUFLLENBQUMsRUFBUCxDQUFVLFNBQVYsRUFBcUIsQ0FBQSxDQUFBLEdBQUE7VUFDbkIsSUFBQyxDQUFBLENBQUQsQ0FBRyxlQUFILENBQW1CLENBQUMsVUFBcEIsQ0FBK0IsVUFBL0I7VUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGVBQUgsQ0FBbUIsQ0FBQyxRQUFwQixDQUE2QixlQUE3QjtVQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsY0FBSCxDQUFrQixDQUFDLEtBQW5CLENBQXlCLElBQXpCO2lCQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsZUFBSCxDQUFtQixDQUFDLEtBQXBCLENBQTBCLElBQTFCO1FBSm1CLENBQXJCO2VBTUEsSUFBQyxDQUFBLEtBQUssQ0FBQyxFQUFQLENBQVUsU0FBVixFQUFxQixDQUFBLENBQUEsR0FBQTtVQUNuQixJQUFDLENBQUEsQ0FBRCxDQUFHLGVBQUgsQ0FBbUIsQ0FBQyxXQUFwQixDQUFnQyxlQUFoQztVQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsY0FBSCxDQUFrQixDQUFDLEtBQW5CLENBQXlCLElBQXpCO2lCQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsZUFBSCxDQUFtQixDQUFDLEtBQXBCLENBQTBCLElBQTFCO1FBSG1CLENBQXJCO01BVFU7O01BY1osTUFBUSxDQUFBLENBQUE7UUFDTixJQUFDLENBQUEsQ0FBRCxDQUFHLG9CQUFILENBQXdCLENBQUMsTUFBekIsQ0FDRTtVQUFBLFdBQUEsRUFBYSxVQUFiO1VBQ0EsR0FBQSxFQUFLLENBREw7VUFFQSxHQUFBLEVBQUssR0FGTDtVQUdBLEtBQUEsRUFBTyxHQUhQO1VBSUEsSUFBQSxFQUFNLENBQUEsQ0FBQSxHQUFBO21CQUNKLElBQUMsQ0FBQSxDQUFELENBQUcsb0JBQUgsQ0FBd0IsQ0FBQyxPQUF6QixDQUFpQyxNQUFqQztVQURJLENBSk47VUFNQSxLQUFBLEVBQU8sQ0FBQyxDQUFELEVBQUksRUFBSixDQUFBLEdBQUE7WUFDTCxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVztjQUFBLFNBQUEsRUFBVyxFQUFFLENBQUM7WUFBZCxDQUFYO21CQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsb0JBQUgsQ0FBd0IsQ0FBQyxPQUF6QixDQUFpQyxNQUFqQztVQUZLO1FBTlAsQ0FERjtRQVdBLElBQUMsQ0FBQSxDQUFELENBQUcsb0JBQUgsQ0FBd0IsQ0FBQyxPQUF6QixDQUNFO1VBQUEsS0FBQSxFQUFPLENBQUEsQ0FBQSxHQUFBO21CQUFHLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLFdBQVg7VUFBSCxDQUFQO1VBQ0EsT0FBQSxFQUFTLEVBRFQ7VUFFQSxTQUFBLEVBQVcsS0FGWDtVQUdBLFNBQUEsRUFBVztRQUhYLENBREY7UUFNQSxTQUFTLENBQUMsWUFBWSxDQUFDLFlBQXZCLENBQW9DO1VBQUMsS0FBQSxFQUFNLElBQVA7VUFBYSxLQUFBLEVBQU07UUFBbkIsQ0FBcEMsQ0FBOEQsQ0FBQyxJQUEvRCxDQUFvRSxDQUFBLENBQUEsR0FBQTtpQkFDbEUsU0FBUyxDQUFDLFlBQVksQ0FBQyxnQkFBdkIsQ0FBQSxDQUF5QyxDQUFDLElBQTFDLENBQStDLENBQUMsT0FBRCxDQUFBLEdBQUE7QUFDN0MsZ0JBQUE7WUFBQSxPQUFBLEdBQVUsQ0FBQyxDQUFDLE1BQUYsQ0FBUyxPQUFULEVBQWtCLFFBQUEsQ0FBQyxDQUFDLElBQUQsRUFBTyxRQUFQLENBQUQsQ0FBQTtxQkFDMUIsSUFBQSxLQUFRO1lBRGtCLENBQWxCO1lBR1YsSUFBVSxDQUFDLENBQUMsT0FBRixDQUFVLE9BQVYsQ0FBVjtBQUFBLHFCQUFBOztZQUVBLE9BQUEsR0FBVSxJQUFDLENBQUEsQ0FBRCxDQUFHLDBCQUFIO1lBRVYsQ0FBQyxDQUFDLElBQUYsQ0FBTyxPQUFQLEVBQWdCLFFBQUEsQ0FBQyxDQUFDLEtBQUQsRUFBTyxRQUFQLENBQUQsQ0FBQTtxQkFDZCxPQUFPLENBQUMsTUFBUixDQUFlLENBQUEsZUFBQSxDQUFBLENBQWtCLFFBQWxCLENBQTJCLEVBQTNCLENBQUEsQ0FBK0IsS0FBL0IsQ0FBcUMsU0FBckMsQ0FBZjtZQURjLENBQWhCO1lBR0EsT0FBTyxDQUFDLElBQVIsQ0FBYSxjQUFiLENBQTRCLENBQUMsSUFBN0IsQ0FBa0MsVUFBbEMsRUFBOEMsSUFBOUM7WUFFQSxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxRQUFYLEVBQXFCLE9BQU8sQ0FBQyxHQUFSLENBQUEsQ0FBckI7WUFFQSxPQUFPLENBQUMsTUFBUixDQUFlLFFBQUEsQ0FBQSxDQUFBO3FCQUNiLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLFFBQVgsRUFBcUIsT0FBTyxDQUFDLEdBQVIsQ0FBQSxDQUFyQjtZQURhLENBQWY7bUJBR0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxtQkFBSCxDQUF1QixDQUFDLElBQXhCLENBQUE7VUFsQjZDLENBQS9DO1FBRGtFLENBQXBFO2VBcUJBO01BdkNNOztNQXlDUixRQUFVLENBQUMsQ0FBRCxDQUFBO1FBQ1IsQ0FBQyxDQUFDLGNBQUYsQ0FBQTtRQUVBLElBQUcsSUFBQyxDQUFBLEtBQUssQ0FBQyxTQUFQLENBQUEsQ0FBSDtBQUNFLGlCQUFPLElBQUMsQ0FBQSxLQUFLLENBQUMsSUFBUCxDQUFBLEVBRFQ7O1FBR0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxlQUFILENBQW1CLENBQUMsSUFBcEIsQ0FBeUI7VUFBQSxRQUFBLEVBQVU7UUFBVixDQUF6QjtlQUNBLElBQUMsQ0FBQSxLQUFLLENBQUMsSUFBUCxDQUFBO01BUFE7O0lBN0RaOzt5QkFDRSxNQUFBLEdBQ0U7TUFBQSxxQkFBQSxFQUEyQixVQUEzQjtNQUNBLG9CQUFBLEVBQTJCLGVBRDNCO01BRUEsUUFBQSxFQUEyQjtJQUYzQjs7Ozs7QUFGSjs7O0FDQUE7RUFBTSxTQUFTLENBQUMsSUFBSSxDQUFDLFFBQXJCLE1BQUEsTUFBQSxRQUFtQyxRQUFRLENBQUMsS0FBNUM7SUFDRSxNQUFRLENBQUEsQ0FBQTtNQUNOLElBQUMsQ0FBQSxDQUFELENBQUcsU0FBSCxDQUFhLENBQUMsTUFBZCxDQUNFO1FBQUEsSUFBQSxFQUFNLENBQUEsQ0FBQSxHQUFBO2lCQUNKLElBQUMsQ0FBQSxDQUFELENBQUcsb0JBQUgsQ0FBd0IsQ0FBQyxPQUF6QixDQUFpQyxNQUFqQztRQURJLENBQU47UUFFQSxLQUFBLEVBQU8sQ0FBQyxDQUFELEVBQUksRUFBSixDQUFBLEdBQUE7VUFDTCxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVztZQUFBLE1BQUEsRUFBUSxFQUFFLENBQUM7VUFBWCxDQUFYO2lCQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsb0JBQUgsQ0FBd0IsQ0FBQyxPQUF6QixDQUFpQyxNQUFqQztRQUZLO01BRlAsQ0FERjtNQU9BLElBQUMsQ0FBQSxDQUFELENBQUcsb0JBQUgsQ0FBd0IsQ0FBQyxPQUF6QixDQUNFO1FBQUEsS0FBQSxFQUFPLENBQUEsQ0FBQSxHQUFBO2lCQUFHLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLFFBQVg7UUFBSCxDQUFQO1FBQ0EsT0FBQSxFQUFTLEVBRFQ7UUFFQSxTQUFBLEVBQVcsS0FGWDtRQUdBLFNBQUEsRUFBVztNQUhYLENBREY7YUFNQTtJQWRNOztFQURWO0FBQUE7OztBQ0FBO0VBQU0sU0FBUyxDQUFDLElBQUksQ0FBQztJQUFyQixNQUFBLFNBQUEsUUFBc0MsU0FBUyxDQUFDLElBQUksQ0FBQyxNQUFyRDtNQWNFLFVBQVksQ0FBQSxDQUFBO2FBQVosQ0FBQSxVQUNFLENBQUE7UUFFQSxJQUFDLENBQUEsS0FBSyxDQUFDLEVBQVAsQ0FBVSxrQkFBVixFQUE4QixDQUFBLENBQUEsR0FBQTtVQUM1QixJQUFDLENBQUEsQ0FBRCxDQUFHLFlBQUgsQ0FBZ0IsQ0FBQyxXQUFqQixDQUE2QixTQUE3QjtpQkFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLENBQUEsV0FBQSxDQUFBLENBQWMsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsV0FBWCxDQUFkLENBQUEsQ0FBSCxDQUEyQyxDQUFDLFFBQTVDLENBQXFELFNBQXJEO1FBRjRCLENBQTlCO1FBSUEsSUFBQyxDQUFBLEtBQUssQ0FBQyxFQUFQLENBQVUsU0FBVixFQUFxQixDQUFBLENBQUEsR0FBQTtVQUNuQixJQUFDLENBQUEsQ0FBRCxDQUFHLGVBQUgsQ0FBbUIsQ0FBQyxVQUFwQixDQUErQixVQUEvQjtVQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsYUFBSCxDQUFpQixDQUFDLElBQWxCLENBQUE7VUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxJQUFuQixDQUFBO1VBQ0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxzQkFBSCxDQUEwQixDQUFDLFdBQTNCLENBQXVDLE9BQXZDLENBQStDLENBQUMsSUFBaEQsQ0FBcUQsRUFBckQ7VUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxLQUFuQixDQUF5QixJQUF6QjtVQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsZUFBSCxDQUFtQixDQUFDLEtBQXBCLENBQTBCLElBQTFCO1VBRUEsSUFBRyxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxVQUFYLENBQUg7bUJBQ0UsSUFBQyxDQUFBLENBQUQsQ0FBRyxrQkFBSCxDQUFzQixDQUFDLEdBQXZCLENBQTJCLFFBQTNCLEVBQXFDLFNBQXJDLEVBREY7V0FBQSxNQUFBO1lBR0UsSUFBQyxDQUFBLENBQUQsQ0FBRyxpQkFBSCxDQUFxQixDQUFDLFFBQXRCLENBQStCLHlCQUEvQjttQkFDQSxJQUFDLENBQUEsZ0JBQUQsQ0FBa0IsR0FBbEIsRUFKRjs7UUFSbUIsQ0FBckI7UUFjQSxJQUFDLENBQUEsS0FBSyxDQUFDLEVBQVAsQ0FBVSxRQUFWLEVBQW9CLENBQUEsQ0FBQSxHQUFBO1VBQ2xCLElBQUMsQ0FBQSxDQUFELENBQUcsYUFBSCxDQUFpQixDQUFDLElBQWxCLENBQUE7VUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxJQUFuQixDQUFBO1VBQ0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxjQUFILENBQWtCLENBQUMsS0FBbkIsQ0FBeUIsSUFBekI7VUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGVBQUgsQ0FBbUIsQ0FBQyxLQUFwQixDQUEwQixJQUExQjtpQkFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLHNCQUFILENBQTBCLENBQUMsUUFBM0IsQ0FBb0MsT0FBcEM7UUFMa0IsQ0FBcEI7UUFPQSxJQUFDLENBQUEsS0FBSyxDQUFDLEVBQVAsQ0FBVSxTQUFWLEVBQXFCLENBQUEsQ0FBQSxHQUFBO1VBQ25CLElBQUMsQ0FBQSxDQUFELENBQUcsYUFBSCxDQUFpQixDQUFDLElBQWxCLENBQUE7VUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxJQUFuQixDQUFBO1VBQ0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxrQkFBSCxDQUFzQixDQUFDLEdBQXZCLENBQTJCLFFBQTNCLEVBQXFDLEVBQXJDO1VBQ0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxpQkFBSCxDQUFxQixDQUFDLFdBQXRCLENBQWtDLHlCQUFsQztVQUNBLElBQUMsQ0FBQSxnQkFBRCxDQUFrQixDQUFsQjtVQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsc0JBQUgsQ0FBMEIsQ0FBQyxXQUEzQixDQUF1QyxPQUF2QyxDQUErQyxDQUFDLElBQWhELENBQXFELEVBQXJEO1VBQ0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxjQUFILENBQWtCLENBQUMsS0FBbkIsQ0FBeUIsSUFBekI7aUJBQ0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxlQUFILENBQW1CLENBQUMsS0FBcEIsQ0FBMEIsSUFBMUI7UUFSbUIsQ0FBckI7ZUFVQSxJQUFDLENBQUEsS0FBSyxDQUFDLEVBQVAsQ0FBVSxpQkFBVixFQUE2QixDQUFBLENBQUEsR0FBQTtBQUMzQixjQUFBLFFBQUEsRUFBQTtVQUFBLElBQUEsQ0FBYyxDQUFBLFFBQUEsR0FBVyxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxVQUFYLENBQVgsQ0FBZDtBQUFBLG1CQUFBOztVQUVBLFFBQUEsR0FBVyxVQUFBLENBQVcsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsVUFBWCxDQUFYO1VBRVgsSUFBQyxDQUFBLGdCQUFELENBQWtCLEtBQUEsR0FBTSxRQUFOLEdBQWUsVUFBQSxDQUFXLFFBQVgsQ0FBakM7aUJBRUEsSUFBQyxDQUFBLENBQUQsQ0FBRyxzQkFBSCxDQUEwQixDQUN4QixJQURGLENBQ08sQ0FBQSxDQUFBLENBQUcsU0FBUyxDQUFDLFlBQVYsQ0FBdUIsUUFBdkIsQ0FBSCxDQUFvQyxHQUFwQyxDQUFBLENBQXlDLFNBQVMsQ0FBQyxZQUFWLENBQXVCLFFBQXZCLENBQXpDLENBQUEsQ0FEUDtRQVAyQixDQUE3QjtNQXRDVTs7TUFnRFosTUFBUSxDQUFBLENBQUE7QUFDTixZQUFBO1FBQUEsSUFBQyxDQUFBLENBQUQsQ0FBRyxnQkFBSCxDQUFvQixDQUFDLE1BQXJCLENBQ0U7VUFBQSxXQUFBLEVBQWEsVUFBYjtVQUNBLEdBQUEsRUFBSyxDQURMO1VBRUEsR0FBQSxFQUFLLEdBRkw7VUFHQSxLQUFBLEVBQU8sR0FIUDtVQUlBLElBQUEsRUFBTSxDQUFBLENBQUEsR0FBQTttQkFDSixJQUFDLENBQUEsQ0FBRCxDQUFHLG9CQUFILENBQXdCLENBQUMsT0FBekIsQ0FBaUMsTUFBakM7VUFESSxDQUpOO1VBTUEsS0FBQSxFQUFPLENBQUMsQ0FBRCxFQUFJLEVBQUosQ0FBQSxHQUFBO1lBQ0wsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVc7Y0FBQSxTQUFBLEVBQVcsRUFBRSxDQUFDO1lBQWQsQ0FBWDttQkFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLG9CQUFILENBQXdCLENBQUMsT0FBekIsQ0FBaUMsTUFBakM7VUFGSztRQU5QLENBREY7UUFXQSxJQUFDLENBQUEsQ0FBRCxDQUFHLG9CQUFILENBQXdCLENBQUMsT0FBekIsQ0FDRTtVQUFBLEtBQUEsRUFBTyxDQUFBLENBQUEsR0FBQTttQkFBRyxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxXQUFYO1VBQUgsQ0FBUDtVQUNBLE9BQUEsRUFBUyxFQURUO1VBRUEsU0FBQSxFQUFXLEtBRlg7VUFHQSxTQUFBLEVBQVc7UUFIWCxDQURGO1FBTUEsS0FBQSxHQUFRLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLE9BQVg7UUFFUixJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxLQUFuQixDQUFBO1FBRUEsSUFBQSxDQUFBLENBQW1CLEtBQUssQ0FBQyxNQUFOLEdBQWUsQ0FBbEMsQ0FBQTtBQUFBLGlCQUFPLEtBQVA7O1FBRUEsQ0FBQyxDQUFDLElBQUYsQ0FBTyxLQUFQLEVBQWMsQ0FBQyxDQUFDLElBQUQsRUFBTyxLQUFQLEVBQWMsUUFBZCxDQUFELEVBQTBCLEtBQTFCLENBQUEsR0FBQTtBQUNaLGNBQUEsS0FBQSxFQUFBO1VBQUEscUJBQUcsS0FBSyxDQUFFLGdCQUFQLEtBQWlCLENBQXBCO1lBQ0UsSUFBQSxHQUFPLFNBQVMsQ0FBQyxZQUFWLENBQXVCLEtBQUssQ0FBQyxNQUE3QixFQURUO1dBQUEsTUFBQTtZQUdFLElBQUEsR0FBTyxNQUhUOztVQUtBLElBQUcsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsV0FBWCxDQUFBLEtBQTJCLEtBQTlCO1lBQ0UsS0FBQSxHQUFRLFVBRFY7V0FBQSxNQUFBO1lBR0UsS0FBQSxHQUFRLEdBSFY7O2lCQUtBLElBQUMsQ0FBQSxDQUFELENBQUcsY0FBSCxDQUFrQixDQUFDLE1BQW5CLENBQTBCLENBQUEsK0JBQUEsQ0FBQSxDQUNTLEtBRFQsRUFBQSxDQUFBLENBQ2tCLEtBRGxCLENBQ3dCLFVBRHhCLENBQUEsQ0FFaEIsS0FBQSxHQUFNLENBRlUsQ0FFUixhQUZRLENBQUEscUJBR2hCLFFBQVEsQ0FBRSxlQUFWLElBQW1CLGVBSEgsQ0FHbUIsYUFIbkIsQ0FBQSxxQkFJaEIsUUFBUSxDQUFFLGdCQUFWLElBQW9CLGdCQUpKLENBSXFCLGFBSnJCLENBQUEsQ0FLaEIsSUFMZ0IsQ0FLWCxZQUxXLENBQTFCO1FBWFksQ0FBZDtRQW9CQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGlCQUFILENBQXFCLENBQUMsSUFBdEIsQ0FBQTtlQUVBO01BOUNNOztNQWdEUixnQkFBa0IsQ0FBQyxPQUFELENBQUE7UUFDaEIsSUFBQyxDQUFBLENBQUQsQ0FBRyxpQkFBSCxDQUFxQixDQUFDLEtBQXRCLENBQTRCLENBQUEsQ0FBQSxDQUFHLE9BQUEsR0FBUSxDQUFBLENBQUUsa0JBQUYsQ0FBcUIsQ0FBQyxLQUF0QixDQUFBLENBQVIsR0FBc0MsR0FBekMsQ0FBNkMsRUFBN0MsQ0FBNUI7ZUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLHFDQUFILENBQXlDLENBQUMsS0FBMUMsQ0FBZ0QsQ0FBQSxDQUFFLGtCQUFGLENBQXFCLENBQUMsS0FBdEIsQ0FBQSxDQUFoRDtNQUZnQjs7TUFJbEIsSUFBTSxDQUFDLE9BQUQsQ0FBQTtRQUNKLElBQUMsQ0FBQSxLQUFLLENBQUMsSUFBUCxDQUFBO1FBQ0EsSUFBQSxDQUFjLENBQUEsSUFBQyxDQUFBLElBQUQsR0FBUSxJQUFDLENBQUEsS0FBSyxDQUFDLFVBQVAsQ0FBa0IsT0FBbEIsQ0FBUixDQUFkO0FBQUEsaUJBQUE7O1FBRUEsSUFBQyxDQUFBLENBQUQsQ0FBRyxlQUFILENBQW1CLENBQUMsSUFBcEIsQ0FBeUI7VUFBQSxRQUFBLEVBQVU7UUFBVixDQUF6QjtlQUNBLElBQUMsQ0FBQSxLQUFLLENBQUMsSUFBUCxDQUFZLElBQUMsQ0FBQSxJQUFiO01BTEk7O01BT04sTUFBUSxDQUFDLENBQUQsQ0FBQTtRQUNOLENBQUMsQ0FBQyxjQUFGLENBQUE7UUFDQSxJQUFHLElBQUMsQ0FBQSxLQUFLLENBQUMsU0FBUCxDQUFBLENBQUg7VUFDRSxJQUFDLENBQUEsS0FBSyxDQUFDLFdBQVAsQ0FBQTtBQUNBLGlCQUZGOztlQUlBLElBQUMsQ0FBQSxJQUFELENBQUE7TUFOTTs7TUFRUixPQUFTLENBQUMsQ0FBRCxDQUFBO1FBQ1AsQ0FBQyxDQUFDLGNBQUYsQ0FBQTtlQUNBLElBQUMsQ0FBQSxLQUFLLENBQUMsV0FBUCxDQUFBO01BRk87O01BSVQsVUFBWSxDQUFDLENBQUQsQ0FBQTtRQUNWLENBQUMsQ0FBQyxjQUFGLENBQUE7UUFDQSxJQUFjLDhCQUFkO0FBQUEsaUJBQUE7O2VBRUEsSUFBQyxDQUFBLElBQUQsQ0FBTTtVQUFBLFFBQUEsRUFBVTtRQUFWLENBQU47TUFKVTs7TUFNWixNQUFRLENBQUMsQ0FBRCxDQUFBO1FBQ04sQ0FBQyxDQUFDLGNBQUYsQ0FBQTtRQUNBLElBQUEsQ0FBYyxJQUFDLENBQUEsS0FBSyxDQUFDLFNBQVAsQ0FBQSxDQUFkO0FBQUEsaUJBQUE7O2VBRUEsSUFBQyxDQUFBLElBQUQsQ0FBQTtNQUpNOztNQU1SLE1BQVEsQ0FBQyxDQUFELENBQUE7UUFDTixDQUFDLENBQUMsY0FBRixDQUFBO1FBRUEsSUFBQyxDQUFBLENBQUQsQ0FBRyxZQUFILENBQWdCLENBQUMsV0FBakIsQ0FBNkIsU0FBN0I7UUFDQSxJQUFDLENBQUEsS0FBSyxDQUFDLElBQVAsQ0FBQTtlQUNBLElBQUMsQ0FBQSxJQUFELEdBQVE7TUFMRjs7TUFPUixNQUFRLENBQUMsQ0FBRCxDQUFBO1FBQ04sQ0FBQyxDQUFDLGNBQUYsQ0FBQTtlQUVBLElBQUMsQ0FBQSxLQUFLLENBQUMsSUFBUCxDQUFhLENBQUMsQ0FBQyxDQUFDLEtBQUYsR0FBVSxDQUFBLENBQUUsQ0FBQyxDQUFDLE1BQUosQ0FBVyxDQUFDLE1BQVosQ0FBQSxDQUFvQixDQUFDLElBQWhDLENBQUEsR0FBd0MsQ0FBQSxDQUFFLENBQUMsQ0FBQyxNQUFKLENBQVcsQ0FBQyxLQUFaLENBQUEsQ0FBckQ7TUFITTs7TUFLUixPQUFTLENBQUEsQ0FBQTtBQUNQLFlBQUE7UUFBQSxLQUFBLEdBQVEsSUFBQyxDQUFBLENBQUQsQ0FBRyxRQUFILENBQWEsQ0FBQSxDQUFBLENBQUUsQ0FBQztRQUN4QixJQUFDLENBQUEsQ0FBRCxDQUFHLFFBQUgsQ0FBWSxDQUFDLElBQWIsQ0FBa0I7VUFBQSxRQUFBLEVBQVU7UUFBVixDQUFsQjtlQUVBLElBQUMsQ0FBQSxLQUFLLENBQUMsV0FBUCxDQUFtQixLQUFuQixFQUEwQixDQUFBLENBQUEsR0FBQTtVQUN4QixJQUFDLENBQUEsQ0FBRCxDQUFHLFFBQUgsQ0FBWSxDQUFDLFVBQWIsQ0FBd0IsVUFBeEIsQ0FBbUMsQ0FBQyxHQUFwQyxDQUF3QyxFQUF4QztpQkFDQSxJQUFDLENBQUEsTUFBRCxDQUFBO1FBRndCLENBQTFCO01BSk87O01BUVQsYUFBZSxDQUFDLENBQUQsQ0FBQTtlQUNiLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXO1VBQUEsV0FBQSxFQUFhLENBQUEsQ0FBRSxDQUFDLENBQUMsTUFBSixDQUFXLENBQUMsRUFBWixDQUFlLFVBQWY7UUFBYixDQUFYO01BRGE7O01BR2YsTUFBUSxDQUFDLENBQUQsQ0FBQTtlQUNOLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXO1VBQUEsSUFBQSxFQUFNLENBQUEsQ0FBRSxDQUFDLENBQUMsTUFBSixDQUFXLENBQUMsRUFBWixDQUFlLFVBQWY7UUFBTixDQUFYO01BRE07O0lBeEtWOzt1QkFDRSxNQUFBLEdBQ0U7TUFBQSxtQkFBQSxFQUEyQixRQUEzQjtNQUNBLG9CQUFBLEVBQTJCLFNBRDNCO01BRUEsaUJBQUEsRUFBMkIsWUFGM0I7TUFHQSxhQUFBLEVBQTJCLFFBSDNCO01BSUEsYUFBQSxFQUEyQixRQUozQjtNQUtBLHNCQUFBLEVBQTJCLFFBTDNCO01BTUEsb0JBQUEsRUFBMkIsZUFOM0I7TUFPQSxlQUFBLEVBQTJCLFNBUDNCO01BUUEscUJBQUEsRUFBMkIsZUFSM0I7TUFTQSxjQUFBLEVBQTJCLFFBVDNCO01BVUEsUUFBQSxFQUEyQjtJQVYzQjs7Ozs7QUFGSjs7O0FDQUE7RUFBTSxTQUFTLENBQUMsSUFBSSxDQUFDO0lBQXJCLE1BQUEsU0FBQSxRQUFzQyxRQUFRLENBQUMsS0FBL0M7TUFjRSxVQUFZLENBQUMsS0FBQSxDQUFELENBQUE7UUFBRSxJQUFDLENBQUE7ZUFDYixJQUFDLENBQUEsS0FBSyxDQUFDLEVBQVAsQ0FBVSxvQkFBVixFQUFnQyxDQUFBLENBQUEsR0FBQTtVQUM5QixJQUFHLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLGFBQVgsQ0FBSDttQkFDRSxJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxRQUFuQixDQUE0QixVQUE1QixDQUF1QyxDQUFDLFdBQXhDLENBQW9ELFVBQXBELEVBREY7V0FBQSxNQUFBO21CQUdFLElBQUMsQ0FBQSxDQUFELENBQUcsY0FBSCxDQUFrQixDQUFDLFFBQW5CLENBQTRCLFVBQTVCLENBQXVDLENBQUMsV0FBeEMsQ0FBb0QsVUFBcEQsRUFIRjs7UUFEOEIsQ0FBaEM7TUFEVTs7TUFPWixNQUFRLENBQUEsQ0FBQTtBQUNOLFlBQUEsT0FBQSxFQUFBO1FBQUEsVUFBQSxHQUFhLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLFlBQVg7UUFDYixJQUFDLENBQUEsQ0FBRCxDQUFHLGFBQUgsQ0FBaUIsQ0FBQyxLQUFsQixDQUFBO1FBQ0EsQ0FBQyxDQUFDLElBQUYsQ0FBTyxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxhQUFYLENBQVAsRUFBa0MsQ0FBQyxJQUFELENBQUEsR0FBQTtBQUNoQyxjQUFBO1VBQUEsUUFBQSxHQUFjLFVBQUEsS0FBYyxJQUFqQixHQUEyQixVQUEzQixHQUEyQztpQkFDdEQsQ0FBQSxDQUFFLENBQUEsZUFBQSxDQUFBLENBQWtCLElBQWxCLENBQXVCLEVBQXZCLENBQUEsQ0FBMkIsUUFBM0IsQ0FBb0MsQ0FBcEMsQ0FBQSxDQUF1QyxJQUF2QyxDQUE0QyxTQUE1QyxDQUFGLENBQXlELENBQ3ZELFFBREYsQ0FDVyxJQUFDLENBQUEsQ0FBRCxDQUFHLGFBQUgsQ0FEWDtRQUZnQyxDQUFsQztRQUtBLE9BQUEsR0FBVSxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxTQUFYO1FBQ1YsSUFBQyxDQUFBLENBQUQsQ0FBRyxVQUFILENBQWMsQ0FBQyxLQUFmLENBQUE7UUFDQSxDQUFDLENBQUMsSUFBRixDQUFPLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXLFVBQVgsQ0FBUCxFQUErQixDQUFDLElBQUQsQ0FBQSxHQUFBO0FBQzdCLGNBQUE7VUFBQSxRQUFBLEdBQWMsT0FBQSxLQUFXLElBQWQsR0FBd0IsVUFBeEIsR0FBd0M7aUJBQ25ELENBQUEsQ0FBRSxDQUFBLGVBQUEsQ0FBQSxDQUFrQixJQUFsQixDQUF1QixFQUF2QixDQUFBLENBQTJCLFFBQTNCLENBQW9DLENBQXBDLENBQUEsQ0FBdUMsSUFBdkMsQ0FBNEMsU0FBNUMsQ0FBRixDQUF5RCxDQUN2RCxRQURGLENBQ1csSUFBQyxDQUFBLENBQUQsQ0FBRyxVQUFILENBRFg7UUFGNkIsQ0FBL0I7ZUFLQTtNQWZNOztNQWlCUixLQUFPLENBQUEsQ0FBQTtlQUNMLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXO1VBQUEsR0FBQSxFQUFLLElBQUMsQ0FBQSxDQUFELENBQUcsTUFBSCxDQUFVLENBQUMsR0FBWCxDQUFBO1FBQUwsQ0FBWDtNQURLOztNQUdQLFNBQVcsQ0FBQyxDQUFELENBQUE7ZUFDVCxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVztVQUFBLE9BQUEsRUFBUyxDQUFBLENBQUUsQ0FBQyxDQUFDLE1BQUosQ0FBVyxDQUFDLEdBQVosQ0FBQTtRQUFULENBQVg7TUFEUzs7TUFHWCxVQUFZLENBQUMsQ0FBRCxDQUFBO2VBQ1YsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVc7VUFBQSxRQUFBLEVBQVUsUUFBQSxDQUFTLENBQUEsQ0FBRSxDQUFDLENBQUMsTUFBSixDQUFXLENBQUMsR0FBWixDQUFBLENBQVQ7UUFBVixDQUFYO01BRFU7O01BR1osWUFBYyxDQUFDLENBQUQsQ0FBQTtlQUNaLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXO1VBQUEsVUFBQSxFQUFZLFFBQUEsQ0FBUyxDQUFBLENBQUUsQ0FBQyxDQUFDLE1BQUosQ0FBVyxDQUFDLEdBQVosQ0FBQSxDQUFUO1FBQVosQ0FBWDtNQURZOztNQUdkLFNBQVcsQ0FBQyxDQUFELENBQUE7ZUFDVCxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVztVQUFBLE9BQUEsRUFBUyxRQUFBLENBQVMsQ0FBQSxDQUFFLENBQUMsQ0FBQyxNQUFKLENBQVcsQ0FBQyxHQUFaLENBQUEsQ0FBVDtRQUFULENBQVg7TUFEUzs7TUFHWCxjQUFnQixDQUFDLENBQUQsQ0FBQTtlQUNkLElBQUMsQ0FBQSxLQUFLLENBQUMsR0FBUCxDQUFXO1VBQUEsWUFBQSxFQUFjLENBQUEsQ0FBRSxDQUFDLENBQUMsTUFBSixDQUFXLENBQUMsRUFBWixDQUFlLFVBQWY7UUFBZCxDQUFYO01BRGM7O01BR2hCLGFBQWUsQ0FBQyxDQUFELENBQUE7UUFDYixDQUFDLENBQUMsY0FBRixDQUFBO2VBRUEsSUFBQyxDQUFBLEtBQUssQ0FBQyxpQkFBUCxDQUFBO01BSGE7O01BS2YsT0FBUyxDQUFDLENBQUQsQ0FBQTtRQUNQLENBQUMsQ0FBQyxjQUFGLENBQUE7UUFFQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxJQUFuQixDQUFBO1FBQ0EsSUFBQyxDQUFBLENBQUQsQ0FBRyxlQUFILENBQW1CLENBQUMsSUFBcEIsQ0FBQTtRQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsZUFBSCxDQUFtQixDQUFDLElBQXBCLENBQXlCO1VBQUEsUUFBQSxFQUFVO1FBQVYsQ0FBekI7UUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLG9DQUFILENBQXdDLENBQUMsVUFBekMsQ0FBb0QsVUFBcEQ7ZUFFQSxJQUFDLENBQUEsSUFBSSxDQUFDLFdBQU4sQ0FBQTtNQVJPOztNQVVULE1BQVEsQ0FBQyxDQUFELENBQUE7UUFDTixDQUFDLENBQUMsY0FBRixDQUFBO1FBRUEsSUFBQyxDQUFBLENBQUQsQ0FBRyxjQUFILENBQWtCLENBQUMsSUFBbkIsQ0FBQTtRQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsZUFBSCxDQUFtQixDQUFDLElBQXBCLENBQUE7UUFDQSxJQUFDLENBQUEsQ0FBRCxDQUFHLGVBQUgsQ0FBbUIsQ0FBQyxVQUFwQixDQUErQixVQUEvQjtRQUNBLElBQUMsQ0FBQSxDQUFELENBQUcsb0NBQUgsQ0FBd0MsQ0FBQyxJQUF6QyxDQUE4QztVQUFBLFFBQUEsRUFBVTtRQUFWLENBQTlDO2VBRUEsSUFBQyxDQUFBLElBQUksQ0FBQyxVQUFOLENBQUE7TUFSTTs7TUFVUixnQkFBa0IsQ0FBQyxDQUFELENBQUE7QUFDaEIsWUFBQSxNQUFBLEVBQUE7UUFBQSxDQUFDLENBQUMsY0FBRixDQUFBO1FBRUEsS0FBQSxHQUFRLElBQUMsQ0FBQSxDQUFELENBQUcseUJBQUgsQ0FBNkIsQ0FBQyxHQUE5QixDQUFBO1FBQ1IsTUFBQSxHQUFTLElBQUMsQ0FBQSxDQUFELENBQUcsd0JBQUgsQ0FBNEIsQ0FBQyxHQUE3QixDQUFBO1FBRVQsSUFBQSxDQUFBLENBQWMsTUFBQSxLQUFVLEVBQVYsSUFBZ0IsS0FBQSxLQUFTLEVBQXZDLENBQUE7QUFBQSxpQkFBQTs7UUFFQSxJQUFDLENBQUEsSUFBSSxDQUFDLFlBQU4sQ0FDRTtVQUFBLE1BQUEsRUFBUSxNQUFSO1VBQ0EsS0FBQSxFQUFRO1FBRFIsQ0FERjtlQUlBLElBQUMsQ0FBQSxDQUFELENBQUcsbUJBQUgsQ0FBdUIsQ0FBQyxJQUF4QixDQUE2QixHQUE3QixFQUFrQyxDQUFBLENBQUEsR0FBQTtBQUNqQyxjQUFBO1VBQUEsRUFBQSxHQUFLLENBQUEsQ0FBQSxHQUFBO21CQUNILElBQUMsQ0FBQSxDQUFELENBQUcsbUJBQUgsQ0FBdUIsQ0FBQyxJQUF4QixDQUE2QixHQUE3QjtVQURHO2lCQUdMLFVBQUEsQ0FBVyxFQUFYLEVBQWUsSUFBZjtRQUppQyxDQUFsQztNQVpnQjs7TUFrQmxCLFFBQVUsQ0FBQyxDQUFELENBQUE7ZUFDUixDQUFDLENBQUMsY0FBRixDQUFBO01BRFE7O0lBbkdaOzt1QkFDRSxNQUFBLEdBQ0U7TUFBQSxhQUFBLEVBQTJCLE9BQTNCO01BQ0Esc0JBQUEsRUFBMkIsV0FEM0I7TUFFQSx1QkFBQSxFQUEyQixZQUYzQjtNQUdBLG9CQUFBLEVBQTJCLGNBSDNCO01BSUEsaUJBQUEsRUFBMkIsV0FKM0I7TUFLQSxzQkFBQSxFQUEyQixnQkFMM0I7TUFNQSxvQkFBQSxFQUEyQixlQU4zQjtNQU9BLHFCQUFBLEVBQTJCLFNBUDNCO01BUUEsb0JBQUEsRUFBMkIsUUFSM0I7TUFTQSx3QkFBQSxFQUEyQixrQkFUM0I7TUFVQSxRQUFBLEVBQTJCO0lBVjNCOzs7OztBQUZKOzs7QUNBQTtFQUFNLFNBQVMsQ0FBQyxJQUFJLENBQUMsUUFBckIsTUFBQSxNQUFBLFFBQW1DLFFBQVEsQ0FBQyxLQUE1QztJQUNFLFVBQVksQ0FBQSxDQUFBO01BQ1YsSUFBQyxDQUFBLEtBQUssQ0FBQyxFQUFQLENBQVUsb0JBQVYsRUFBZ0MsQ0FBQSxDQUFBLEdBQUE7UUFDOUIsSUFBRyxJQUFDLENBQUEsS0FBSyxDQUFDLEdBQVAsQ0FBVyxhQUFYLENBQUg7aUJBQ0UsSUFBQyxDQUFBLENBQUQsQ0FBRyxjQUFILENBQWtCLENBQUMsUUFBbkIsQ0FBNEIsVUFBNUIsQ0FBdUMsQ0FBQyxXQUF4QyxDQUFvRCxVQUFwRCxFQURGO1NBQUEsTUFBQTtpQkFHRSxJQUFDLENBQUEsQ0FBRCxDQUFHLGNBQUgsQ0FBa0IsQ0FBQyxRQUFuQixDQUE0QixVQUE1QixDQUF1QyxDQUFDLFdBQXhDLENBQW9ELFVBQXBELEVBSEY7O01BRDhCLENBQWhDO01BTUEsSUFBQyxDQUFBLEtBQUssQ0FBQyxFQUFQLENBQVUsbUJBQVYsRUFBK0IsQ0FBQSxDQUFBLEdBQUE7ZUFDN0IsSUFBQyxDQUFBLENBQUQsQ0FBRyxjQUFILENBQWtCLENBQUMsS0FBbkIsQ0FBeUIsQ0FBQSxDQUFBLENBQUcsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsWUFBWCxDQUFILENBQTRCLENBQTVCLENBQXpCO01BRDZCLENBQS9CO2FBR0EsSUFBQyxDQUFBLEtBQUssQ0FBQyxFQUFQLENBQVUsb0JBQVYsRUFBZ0MsQ0FBQSxDQUFBLEdBQUE7ZUFDOUIsSUFBQyxDQUFBLENBQUQsQ0FBRyxlQUFILENBQW1CLENBQUMsS0FBcEIsQ0FBMEIsQ0FBQSxDQUFBLENBQUcsSUFBQyxDQUFBLEtBQUssQ0FBQyxHQUFQLENBQVcsYUFBWCxDQUFILENBQTZCLENBQTdCLENBQTFCO01BRDhCLENBQWhDO0lBVlU7O0lBYVosYUFBZSxDQUFDLENBQUQsQ0FBQTtNQUNiLENBQUMsQ0FBQyxjQUFGLENBQUE7YUFFQSxJQUFDLENBQUEsS0FBSyxDQUFDLGlCQUFQLENBQUE7SUFIYTs7SUFLZixRQUFVLENBQUMsQ0FBRCxDQUFBO2FBQ1IsQ0FBQyxDQUFDLGNBQUYsQ0FBQTtJQURROztFQW5CWjtBQUFBOzs7QUNBQTtFQUFNLFNBQVMsQ0FBQyxLQUFLLENBQUMsYUFBdEIsTUFBQSxXQUFBLFFBQXlDLFNBQVMsQ0FBQyxLQUFLLENBQUMsTUFBekQ7SUFDRSxVQUFZLENBQUEsQ0FBQTtXQUFaLENBQUEsVUFDRSxDQUFBO2FBRUEsSUFBQyxDQUFBLEVBQUQsQ0FBSSxlQUFKLEVBQXFCLFFBQUEsQ0FBQSxDQUFBO1FBQ25CLElBQWMsbUJBQWQ7QUFBQSxpQkFBQTs7ZUFDQSxJQUFDLENBQUEsWUFBRCxDQUFBO01BRm1CLENBQXJCO0lBSFU7O0lBT1osWUFBYyxDQUFDLEVBQUQsQ0FBQTtBQUNaLFVBQUE7TUFBQSxJQUFtQyxtQkFBbkM7UUFBQSxJQUFDLENBQUEsTUFBTSxDQUFDLFVBQVIsQ0FBbUIsSUFBQyxDQUFBLFdBQXBCLEVBQUE7O01BRUEsV0FBQSxHQUFjO1FBQUMsS0FBQSxFQUFNO01BQVA7TUFFZCxJQUFHLElBQUMsQ0FBQSxHQUFELENBQUssUUFBTCxDQUFIO1FBQ0UsV0FBVyxDQUFDLEtBQVosR0FDRTtVQUFBLEtBQUEsRUFBTyxJQUFDLENBQUEsR0FBRCxDQUFLLFFBQUw7UUFBUCxFQUZKO09BQUEsTUFBQTtRQUlFLFdBQVcsQ0FBQyxLQUFaLEdBQW9CLEtBSnRCOzthQU1BLElBQUMsQ0FBQSxJQUFJLENBQUMsc0JBQU4sQ0FBNkIsV0FBN0IsRUFBMEMsT0FBQSxDQUFBLEdBQUE7UUFBQyxJQUFDLENBQUE7UUFDMUMsSUFBQyxDQUFBLE1BQU0sQ0FBQyxPQUFSLENBQWdCLElBQUMsQ0FBQSxXQUFqQjswQ0FDQTtNQUZ3QyxDQUExQztJQVhZOztJQWVkLElBQU0sQ0FBQSxDQUFBO01BQ0osSUFBQyxDQUFBLE9BQUQsQ0FBQTthQUVBLElBQUMsQ0FBQSxZQUFELENBQWMsQ0FBQSxDQUFBLEdBQUE7ZUFDWixJQUFDLENBQUEsT0FBRCxDQUFTLFNBQVQ7TUFEWSxDQUFkO0lBSEk7O0VBdkJSO0FBQUE7OztBQ0FBO0VBQU0sU0FBUyxDQUFDLEtBQUssQ0FBQyxRQUF0QixNQUFBLE1BQUEsUUFBb0MsUUFBUSxDQUFDLE1BQTdDO0lBQ0UsU0FBVyxDQUFDLFFBQUQsQ0FBQTtNQUNULElBQUcsUUFBQSxHQUFXLEdBQWQ7QUFDRSxlQUFPLENBQUEsR0FBRSxTQURYOzthQUdBO0lBSlM7O0lBTVgsU0FBVyxDQUFBLENBQUE7YUFDVCxVQUFBLENBQVcsSUFBQyxDQUFBLEdBQUQsQ0FBSyxRQUFMLENBQVgsQ0FBQSxHQUEyQjtJQURsQjs7SUFHWCxhQUFlLENBQUEsQ0FBQTthQUNiLElBQUMsQ0FBQSxTQUFELENBQVcsR0FBQSxHQUFNLElBQUMsQ0FBQSxTQUFELENBQUEsQ0FBakI7SUFEYTs7SUFHZixjQUFnQixDQUFBLENBQUE7YUFDZCxJQUFDLENBQUEsU0FBRCxDQUFXLElBQUMsQ0FBQSxTQUFELENBQUEsQ0FBWDtJQURjOztFQWJsQjtBQUFBOzs7QUNBQTtBQUFBLE1BQUEsR0FBQTtJQUFBOztRQUFNLFNBQVMsQ0FBQyxLQUFLLENBQUMsV0FBdEIsTUFBQSxTQUFBLFFBQXVDLFNBQVMsQ0FBQyxLQUFLLENBQUMsTUFBdkQ7OztVQVdFLENBQUEsaUJBQUEsQ0FBQTs7O0lBVkEsVUFBWSxDQUFBLENBQUE7V0FBWixDQUFBLFVBQ0UsQ0FBQTtNQUVBLElBQUMsQ0FBQSxLQUFLLENBQUMsRUFBUCxDQUFVLGVBQVYsRUFBMkIsSUFBQyxDQUFBLFVBQTVCO01BRUEsSUFBQyxDQUFBLE9BQUQsR0FBVyxJQUFDLENBQUEsSUFBSSxDQUFDLE9BQU8sQ0FBQyxVQUFkLENBQUE7TUFDWCxJQUFDLENBQUEsT0FBTyxDQUFDLE9BQVQsQ0FBaUIsSUFBQyxDQUFBLElBQUksQ0FBQyxPQUF2QjthQUVBLElBQUMsQ0FBQSxJQUFELEdBQVEsSUFBQyxDQUFBO0lBUkM7O0lBVVosVUFBWSxDQUFBLENBQUE7O01BQ1YsSUFBYyxvQkFBZDtBQUFBLGVBQUE7O01BRUEsSUFBRyxJQUFDLENBQUEsR0FBRCxDQUFLLE1BQUwsQ0FBQSxLQUFnQixNQUFuQjtlQUNFLElBQUMsQ0FBQSxPQUFPLENBQUMsSUFBSSxDQUFDLEtBQWQsR0FBc0IsSUFBQyxDQUFBLEtBQUssQ0FBQyxhQUFQLENBQUEsRUFEeEI7T0FBQSxNQUFBO2VBR0UsSUFBQyxDQUFBLE9BQU8sQ0FBQyxJQUFJLENBQUMsS0FBZCxHQUFzQixJQUFDLENBQUEsS0FBSyxDQUFDLGNBQVAsQ0FBQSxFQUh4Qjs7SUFIVTs7SUFRWixXQUFhLENBQUMsUUFBRCxFQUFXLEVBQVgsQ0FBQTtBQUNYLFVBQUEsT0FBQSxFQUFBLEtBQUEsRUFBQSxDQUFBLEVBQUEsQ0FBQSxFQUFBLE1BQUEsRUFBQSxJQUFBLEVBQUE7TUFBQSxLQUFBLEdBQVEsSUFBQyxDQUFBLEdBQUQsQ0FBSyxPQUFMO01BRVIsTUFBQSxHQUFTLENBQUMsQ0FBQyxLQUFGLENBQVEsUUFBUSxDQUFDLE1BQWpCLEVBQXlCLENBQUEsQ0FBQSxHQUFBO1FBQ2hDLElBQUMsQ0FBQSxHQUFELENBQUs7VUFBQSxLQUFBLEVBQU87UUFBUCxDQUFMOzBDQUNBO01BRmdDLENBQXpCO01BSVQsT0FBQSxHQUFVLFFBQUEsQ0FBQyxJQUFELENBQUE7ZUFDUixJQUFJLENBQUMsa0JBQUwsQ0FBd0IsQ0FBQyxJQUFELENBQUEsR0FBQTtVQUN0QixLQUFLLENBQUMsSUFBTixDQUNFO1lBQUEsSUFBQSxFQUFXLElBQVg7WUFDQSxLQUFBLEVBQVcsSUFBSSxDQUFDLEtBRGhCO1lBRUEsUUFBQSxFQUFXLElBQUksQ0FBQztVQUZoQixDQURGO2lCQUtBLE1BQUEsQ0FBQTtRQU5zQixDQUF4QjtNQURRO0FBU1U7TUFBQSxLQUFTLHFHQUFUO3FCQUFwQixPQUFBLENBQVEsUUFBUyxDQUFBLENBQUEsQ0FBakI7TUFBb0IsQ0FBQTs7SUFoQlQ7O0lBa0JiLFVBQVksQ0FBQyxVQUFVLENBQUEsQ0FBWCxDQUFBO0FBQ1YsVUFBQSxJQUFBLEVBQUEsS0FBQSxFQUFBO01BQUEsS0FBQSxHQUFRLElBQUMsQ0FBQSxHQUFELENBQUssT0FBTDtNQUNSLEtBQUEsR0FBUSxJQUFDLENBQUEsR0FBRCxDQUFLLFdBQUw7TUFFUixJQUFVLEtBQUssQ0FBQyxNQUFOLEtBQWdCLENBQTFCO0FBQUEsZUFBQTs7TUFFQSxLQUFBLElBQVksT0FBTyxDQUFDLFFBQVgsR0FBeUIsQ0FBQyxDQUExQixHQUFpQztNQUUxQyxJQUEwQixLQUFBLEdBQVEsQ0FBbEM7UUFBQSxLQUFBLEdBQVEsS0FBSyxDQUFDLE1BQU4sR0FBYSxFQUFyQjs7TUFFQSxJQUFHLEtBQUEsSUFBUyxLQUFLLENBQUMsTUFBbEI7UUFDRSxJQUFBLENBQU8sSUFBQyxDQUFBLEdBQUQsQ0FBSyxNQUFMLENBQVA7VUFDRSxJQUFDLENBQUEsR0FBRCxDQUFLO1lBQUEsU0FBQSxFQUFXLENBQUM7VUFBWixDQUFMO0FBQ0EsaUJBRkY7O1FBSUEsSUFBRyxLQUFBLEdBQVEsQ0FBWDtVQUNFLEtBQUEsR0FBUSxLQUFLLENBQUMsTUFBTixHQUFhLEVBRHZCO1NBQUEsTUFBQTtVQUdFLEtBQUEsR0FBUSxFQUhWO1NBTEY7O01BVUEsSUFBQSxHQUFPLEtBQU0sQ0FBQSxLQUFBO01BQ2IsSUFBQyxDQUFBLEdBQUQsQ0FBSztRQUFBLFNBQUEsRUFBVztNQUFYLENBQUw7YUFFQTtJQXZCVTs7SUF5QlosSUFBTSxDQUFDLElBQUQsQ0FBQTtNQUNKLElBQUMsQ0FBQSxPQUFELENBQUE7TUFFQSxJQUFDLENBQUEsVUFBRCxDQUFBO2FBRUEsSUFBQyxDQUFBLElBQUksQ0FBQyxnQkFBTixDQUF1QixJQUF2QixFQUE2QixJQUE3QixFQUFtQyxPQUFBLENBQUEsR0FBQTtBQUNqQyxZQUFBO1FBRGtDLElBQUMsQ0FBQTtRQUNuQyxJQUFDLENBQUEsTUFBTSxDQUFDLE9BQVIsQ0FBZ0IsSUFBQyxDQUFBLFdBQWpCO1FBRUEsSUFBRyw0QkFBSDtVQUNFLElBQUMsQ0FBQSxHQUFELENBQUs7WUFBQSxRQUFBLEVBQVUsSUFBQyxDQUFBLE1BQU0sQ0FBQyxRQUFSLENBQUE7VUFBVixDQUFMLEVBREY7U0FBQSxNQUFBO1VBR0UsSUFBZ0QsNERBQWhEO1lBQUEsSUFBQyxDQUFBLEdBQUQsQ0FBSztjQUFBLFFBQUEsRUFBVSxVQUFBLENBQVcsSUFBSSxDQUFDLEtBQUssQ0FBQyxNQUF0QjtZQUFWLENBQUwsRUFBQTtXQUhGOztRQUtBLElBQUMsQ0FBQSxNQUFNLENBQUMsSUFBUixDQUFhLElBQWI7ZUFDQSxJQUFDLENBQUEsT0FBRCxDQUFTLFNBQVQ7TUFUaUMsQ0FBbkM7SUFMSTs7SUFnQk4sS0FBTyxDQUFBLENBQUE7TUFDTCxJQUFDLENBQUEsSUFBRCxDQUFBO01BRUEsSUFBdUIsSUFBQyxDQUFBLEdBQUQsQ0FBSyxhQUFMLENBQXZCO2VBQUEsSUFBQyxDQUFBLElBQUQsQ0FBTSxJQUFDLENBQUEsVUFBRCxDQUFBLENBQU4sRUFBQTs7SUFISzs7RUE5RVQ7QUFBQTs7O0FDQUE7RUFBTSxTQUFTLENBQUMsS0FBSyxDQUFDLFdBQXRCLE1BQUEsU0FBQSxRQUF1QyxRQUFRLENBQUMsTUFBaEQ7SUFDRSxVQUFZLENBQUMsVUFBRCxFQUFhLE9BQWIsQ0FBQTtNQUNWLElBQUMsQ0FBQSxLQUFELEdBQVMsT0FBTyxDQUFDO2FBRWpCLElBQUMsQ0FBQSxLQUFLLENBQUMsRUFBUCxDQUFVLEtBQVYsRUFBaUIsQ0FBQSxDQUFBLEdBQUE7ZUFDZixJQUFDLENBQUEsR0FBRCxDQUFLO1VBQUEsV0FBQSxFQUFhO1FBQWIsQ0FBTDtNQURlLENBQWpCO0lBSFU7O0lBTVosaUJBQW1CLENBQUEsQ0FBQTtBQUNqQixVQUFBO01BQUEsV0FBQSxHQUFjLElBQUMsQ0FBQSxHQUFELENBQUssYUFBTDtNQUNkLElBQUcsV0FBSDtlQUNFLElBQUMsQ0FBQSxHQUFELENBQUs7VUFBQSxXQUFBLEVBQWE7UUFBYixDQUFMLEVBREY7T0FBQSxNQUFBO1FBR0UsSUFBQyxDQUFBLEtBQUssQ0FBQyxPQUFQLENBQWUsS0FBZjtlQUNBLElBQUMsQ0FBQSxHQUFELENBQUs7VUFBQSxXQUFBLEVBQWE7UUFBYixDQUFMLEVBSkY7O0lBRmlCOztFQVByQjtBQUFBOzs7QUNBQTtBQUFBLE1BQUEsR0FBQTtJQUFBOztRQUFNLFNBQVMsQ0FBQyxLQUFLLENBQUMsUUFBdEIsTUFBQSxNQUFBLFFBQW9DLFFBQVEsQ0FBQyxNQUE3Qzs7O1VBMkVFLENBQUEsbUJBQUEsQ0FBQTs7O0lBMUVBLFVBQVksQ0FBQyxVQUFELEVBQWEsT0FBYixDQUFBO01BQ1YsSUFBQyxDQUFBLElBQUQsR0FBUSxPQUFPLENBQUM7TUFDaEIsSUFBQyxDQUFBLEtBQUQsR0FBUyxPQUFPLENBQUM7TUFFakIsSUFBQyxDQUFBLEtBQUssQ0FBQyxFQUFQLENBQVUsS0FBVixFQUFpQixDQUFBLENBQUEsR0FBQTtlQUNmLElBQUMsQ0FBQSxHQUFELENBQUs7VUFBQSxXQUFBLEVBQWE7UUFBYixDQUFMO01BRGUsQ0FBakI7TUFHQSxJQUFDLENBQUEsRUFBRCxDQUFJLGtCQUFKLEVBQXdCLElBQUMsQ0FBQSxZQUF6QjtNQUNBLElBQUMsQ0FBQSxFQUFELENBQUksT0FBSixFQUFhLElBQUMsQ0FBQSxJQUFkO2FBRUEsSUFBQyxDQUFBLElBQUQsR0FBUSxJQUFDLENBQUEsSUFBSSxDQUFDO0lBVko7O0lBWVosaUJBQW1CLENBQUEsQ0FBQTtBQUNqQixVQUFBO01BQUEsV0FBQSxHQUFjLElBQUMsQ0FBQSxHQUFELENBQUssYUFBTDtNQUNkLElBQUcsV0FBSDtlQUNFLElBQUMsQ0FBQSxHQUFELENBQUs7VUFBQSxXQUFBLEVBQWE7UUFBYixDQUFMLEVBREY7T0FBQSxNQUFBO1FBR0UsSUFBQyxDQUFBLEtBQUssQ0FBQyxPQUFQLENBQWUsS0FBZjtlQUNBLElBQUMsQ0FBQSxHQUFELENBQUs7VUFBQSxXQUFBLEVBQWE7UUFBYixDQUFMLEVBSkY7O0lBRmlCOztJQVFuQixTQUFXLENBQUEsQ0FBQTthQUNUO0lBRFM7O0lBR1gsa0JBQW9CLENBQUEsQ0FBQTtBQUNsQixVQUFBLFlBQUEsRUFBQSxTQUFBLEVBQUEsVUFBQSxFQUFBLEtBQUEsRUFBQTtNQUFBLFVBQUEsR0FBYTtNQUNiLFlBQUEsR0FBZSxVQUFBLENBQVcsVUFBWCxDQUFBLEdBQXVCLFVBQUEsQ0FBVyxJQUFDLENBQUEsSUFBSSxDQUFDLE9BQU8sQ0FBQyxVQUF6QjtNQUV0QyxTQUFBLEdBQVksSUFBSSxDQUFDLEdBQUwsQ0FBUyxVQUFBLENBQVcsVUFBWCxDQUFUO01BQ1osS0FBQSxHQUFZLEdBQUEsR0FBTSxJQUFJLENBQUMsR0FBTCxDQUFTLEVBQVQ7TUFFbEIsTUFBQSxHQUFTLElBQUMsQ0FBQSxJQUFJLENBQUMsT0FBTyxDQUFDLHFCQUFkLENBQW9DLFVBQXBDLEVBQWdELENBQWhELEVBQW1ELENBQW5EO01BRVQsTUFBTSxDQUFDLGNBQVAsR0FBd0IsQ0FBQyxHQUFELENBQUEsR0FBQTtBQUN0QixZQUFBLE9BQUEsRUFBQSxXQUFBLEVBQUEsQ0FBQSxFQUFBLENBQUEsRUFBQSxDQUFBLEVBQUEsSUFBQSxFQUFBLElBQUEsRUFBQSxJQUFBLEVBQUEsT0FBQSxFQUFBLEdBQUEsRUFBQSxHQUFBLEVBQUE7UUFBQSxHQUFBLEdBQU0sQ0FBQTtRQUVOLElBQUcsK0RBQUg7VUFDRSxHQUFJLENBQUEsVUFBQSxDQUFKLEdBQWtCLElBQUMsQ0FBQSxNQUFNLENBQUMsUUFBUixDQUFBLEVBRHBCO1NBQUEsTUFBQTtVQUdFLElBQUcsbUJBQUg7WUFDRSxHQUFJLENBQUEsVUFBQSxDQUFKLEdBQWtCLFVBQUEsQ0FBVyxJQUFDLENBQUEsR0FBRCxDQUFLLFVBQUwsQ0FBWCxDQUFBLEdBQTZCLGFBRGpEO1dBSEY7O0FBTUE7UUFBQSxLQUFlLGtJQUFmO1VBQ0UsV0FBQSxHQUFjLEdBQUcsQ0FBQyxXQUFXLENBQUMsY0FBaEIsQ0FBK0IsT0FBL0I7VUFFZCxHQUFBLEdBQU07VUFDTixLQUFTLHdHQUFUO1lBQ0UsR0FBQSxJQUFPLElBQUksQ0FBQyxHQUFMLENBQVMsV0FBWSxDQUFBLENBQUEsQ0FBckIsRUFBeUIsQ0FBekI7VUFEVDtVQUVBLE1BQUEsR0FBUyxHQUFBLEdBQUksSUFBSSxDQUFDLEdBQUwsQ0FBUyxDQUFDLElBQUksQ0FBQyxHQUFMLENBQVMsR0FBVCxDQUFBLEdBQWMsU0FBZixDQUFBLEdBQTBCLEtBQW5DO1VBRWIsSUFBRyxPQUFBLEtBQVcsQ0FBZDtZQUNFLEdBQUksQ0FBQSxZQUFBLENBQUosR0FBb0IsT0FEdEI7V0FBQSxNQUFBO1lBR0UsR0FBSSxDQUFBLGFBQUEsQ0FBSixHQUFxQixPQUh2Qjs7VUFLQSxJQUFDLENBQUEsR0FBRCxDQUFLLEdBQUw7dUJBRUEsR0FBRyxDQUFDLFlBQVksQ0FBQyxjQUFqQixDQUFnQyxPQUFoQyxDQUF3QyxDQUFDLEdBQXpDLENBQTZDLFdBQTdDO1FBZkYsQ0FBQTs7TUFUc0I7YUEwQnhCO0lBbkNrQjs7SUFxQ3BCLGlCQUFtQixDQUFBLENBQUE7QUFDakIsVUFBQTtNQUFBLE1BQUEsR0FBUyxJQUFDLENBQUEsSUFBSSxDQUFDLE9BQU8sQ0FBQyxxQkFBZCxDQUFvQyxHQUFwQyxFQUF5QyxDQUF6QyxFQUE0QyxDQUE1QztNQUVULE1BQU0sQ0FBQyxjQUFQLEdBQXdCLENBQUMsR0FBRCxDQUFBLEdBQUE7QUFDdEIsWUFBQSxPQUFBLEVBQUEsV0FBQSxFQUFBLENBQUEsRUFBQSxJQUFBLEVBQUE7UUFBQSxXQUFBLEdBQWMsR0FBRyxDQUFDLFdBQVcsQ0FBQyxjQUFoQixDQUErQixPQUEvQjtBQUVkO1FBQUEsS0FBZSxrSUFBZjtVQUNFLElBQUcsSUFBQyxDQUFBLEdBQUQsQ0FBSyxhQUFMLENBQUg7eUJBQ0UsR0FBRyxDQUFDLFlBQVksQ0FBQyxjQUFqQixDQUFnQyxPQUFoQyxDQUF3QyxDQUFDLEdBQXpDLENBQTZDLFdBQTdDLEdBREY7V0FBQSxNQUFBO3lCQUdFLEdBQUcsQ0FBQyxZQUFZLENBQUMsY0FBakIsQ0FBZ0MsT0FBaEMsQ0FBd0MsQ0FBQyxHQUF6QyxDQUE4QyxJQUFJLFlBQUosQ0FBaUIsV0FBVyxDQUFDLE1BQTdCLENBQTlDLEdBSEY7O1FBREYsQ0FBQTs7TUFIc0I7YUFTeEI7SUFaaUI7O0lBY25CLFlBQWMsQ0FBQSxDQUFBOztNQUNaLElBQWMsc0JBQWQ7QUFBQSxlQUFBOzthQUNBLElBQUMsQ0FBQSxTQUFTLENBQUMsSUFBSSxDQUFDLEtBQWhCLEdBQXdCLFVBQUEsQ0FBVyxJQUFDLENBQUEsR0FBRCxDQUFLLFdBQUwsQ0FBWCxDQUFBLEdBQThCO0lBRjFDOztJQUlkLE9BQVMsQ0FBQSxDQUFBO01BQ1AsSUFBQyxDQUFBLFlBQUQsR0FBZ0IsSUFBQyxDQUFBLGtCQUFELENBQUE7TUFDaEIsSUFBQyxDQUFBLFlBQVksQ0FBQyxPQUFkLENBQXNCLElBQUMsQ0FBQSxJQUF2QjtNQUVBLElBQUMsQ0FBQSxTQUFELEdBQWEsSUFBQyxDQUFBLElBQUksQ0FBQyxPQUFPLENBQUMsVUFBZCxDQUFBO01BQ2IsSUFBQyxDQUFBLFNBQVMsQ0FBQyxPQUFYLENBQW1CLElBQUMsQ0FBQSxZQUFwQjtNQUNBLElBQUMsQ0FBQSxZQUFELENBQUE7TUFFQSxJQUFDLENBQUEsV0FBRCxHQUFlLElBQUMsQ0FBQTtNQUVoQixJQUFDLENBQUEsV0FBRCxHQUFlLElBQUMsQ0FBQSxpQkFBRCxDQUFBO01BQ2YsSUFBQyxDQUFBLFdBQVcsQ0FBQyxPQUFiLENBQXFCLElBQUMsQ0FBQSxJQUFJLENBQUMsT0FBTyxDQUFDLFdBQW5DO2FBQ0EsSUFBQyxDQUFBLFdBQVcsQ0FBQyxPQUFiLENBQXFCLElBQUMsQ0FBQSxXQUF0QjtJQVpPOztJQWVULFdBQWEsQ0FBQSxDQUFBO0FBQ1gsVUFBQSxJQUFBLEVBQUE7TUFBQSxJQUFjLDREQUFkO0FBQUEsZUFBQTs7TUFFQSwyRUFBVSxDQUFFLDBCQUFaO1FBQ0UsSUFBQyxDQUFBLE1BQU0sQ0FBQyxJQUFSLENBQUE7ZUFDQSxJQUFDLENBQUEsT0FBRCxDQUFTLFNBQVQsRUFGRjtPQUFBLE1BQUE7UUFJRSxJQUFDLENBQUEsTUFBTSxDQUFDLEtBQVIsQ0FBQTtlQUNBLElBQUMsQ0FBQSxPQUFELENBQVMsUUFBVCxFQUxGOztJQUhXOztJQVViLElBQU0sQ0FBQSxDQUFBO0FBQ0osVUFBQSxJQUFBLEVBQUEsSUFBQSxFQUFBLElBQUEsRUFBQSxJQUFBLEVBQUE7OztjQUFPLENBQUU7Ozs7WUFDRixDQUFFLFVBQVQsQ0FBQTs7O1lBQ1UsQ0FBRSxVQUFaLENBQUE7OztZQUNhLENBQUUsVUFBZixDQUFBOzs7WUFDWSxDQUFFLFVBQWQsQ0FBQTs7TUFFQSxJQUFDLENBQUEsTUFBRCxHQUFVLElBQUMsQ0FBQSxTQUFELEdBQWEsSUFBQyxDQUFBLFlBQUQsR0FBZ0IsSUFBQyxDQUFBLFdBQUQsR0FBZTtNQUV0RCxJQUFDLENBQUEsR0FBRCxDQUFLO1FBQUEsUUFBQSxFQUFVO01BQVYsQ0FBTDthQUNBLElBQUMsQ0FBQSxPQUFELENBQVMsU0FBVDtJQVZJOztJQVlOLElBQU0sQ0FBQyxPQUFELENBQUE7QUFDSixVQUFBLFFBQUEsRUFBQTtNQUFBLElBQUEsQ0FBYyxDQUFBLFFBQUEsd0VBQWtCLENBQUUsS0FBTSwwQkFBMUIsQ0FBZDtBQUFBLGVBQUE7O2FBRUEsSUFBQyxDQUFBLEdBQUQsQ0FBSztRQUFBLFFBQUEsRUFBVTtNQUFWLENBQUw7SUFISTs7SUFLTixZQUFjLENBQUMsSUFBRCxDQUFBO2FBQ1osSUFBQyxDQUFBLElBQUksQ0FBQyxZQUFOLENBQW1CLElBQUksQ0FBQyxRQUF4QjtJQURZOztFQXpIaEI7QUFBQSIsImZpbGUiOiJ3ZWJjYXN0ZXIuanMiLCJzb3VyY2VzQ29udGVudCI6WyJuYXZpZ2F0b3IubWVkaWFEZXZpY2VzIHx8PSB7fVxuXG5uYXZpZ2F0b3IubWVkaWFEZXZpY2VzLmdldFVzZXJNZWRpYSB8fD0gKGNvbnN0cmFpbnRzKSAtPlxuICBmbiA9IG5hdmlnYXRvci5nZXRVc2VyTWVkaWEgfHwgbmF2aWdhdG9yLndlYmtpdEdldFVzZXJNZWRpYSB8fCBuYXZpZ2F0b3IubW96R2V0VXNlck1lZGlhIHx8IG5hdmlnYXRvci5tc0dldFVzZXJNZWRpYVxuXG4gIHVubGVzcyBmbj9cbiAgICByZXR1cm4gUHJvbWlzZS5yZWplY3QgbmV3IEVycm9yKFwiZ2V0VXNlck1lZGlhIGlzIG5vdCBpbXBsZW1lbnRlZCBpbiB0aGlzIGJyb3dzZXJcIilcblxuICBuZXcgUHJvbWlzZSAocmVzb2x2ZSwgcmVqZWN0KSAtPlxuICAgIGZuLmNhbGwgbmF2aWdhdG9yLCBjb25zdHJhaW50cywgcmVzb2x2ZSwgcmVqZWN0XG5cbm5hdmlnYXRvci5tZWRpYURldmljZXMuZW51bWVyYXRlRGV2aWNlcyB8fD0gLT5cbiAgUHJvbWlzZS5yZWplY3QgbmV3IEVycm9yKFwiZW51bWVyYXRlRGV2aWNlcyBpcyBub3QgaW1wbGVtZW50ZWQgb24gdGhpcyBicm93c2VyXCIpXG4iLCIkIC0+XG4gIFdlYmNhc3Rlci5taXhlciA9IG5ldyBXZWJjYXN0ZXIuTW9kZWwuTWl4ZXJcbiAgICBzbGlkZXI6IDBcblxuICBXZWJjYXN0ZXIuc2V0dGluZ3MgPSBuZXcgV2ViY2FzdGVyLk1vZGVsLlNldHRpbmdzKHtcbiAgICB1cmk6ICAgICAgICAgIFwid3M6Ly9zb3VyY2U6aGFja21lQGxvY2FsaG9zdDo4MDgwL21vdW50XCJcbiAgICBiaXRyYXRlOiAgICAgIDEyOFxuICAgIGJpdHJhdGVzOiAgICAgWyA4LCAxNiwgMjQsIDMyLCA0MCwgNDgsIDU2LFxuICAgICAgICAgICAgICAgICAgICA2NCwgODAsIDk2LCAxMTIsIDEyOCwgMTQ0LFxuICAgICAgICAgICAgICAgICAgICAxNjAsIDE5MiwgMjI0LCAyNTYsIDMyMCBdXG4gICAgc2FtcGxlcmF0ZTogICA0NDEwMFxuICAgIHNhbXBsZXJhdGVzOiAgWyA4MDAwLCAxMTAyNSwgMTIwMDAsIDE2MDAwLFxuICAgICAgICAgICAgICAgICAgICAyMjA1MCwgMjQwMDAsIDMyMDAwLCA0NDEwMCwgNDgwMDAgXVxuICAgIGNoYW5uZWxzOiAgICAgMlxuICAgIGVuY29kZXI6ICAgICAgXCJtcDNcIlxuICAgIGFzeW5jaHJvbm91czogdHJ1ZVxuICAgIHBhc3NUaHJvdWdoOiAgZmFsc2VcbiAgfSwge1xuICAgIG1peGVyOiBXZWJjYXN0ZXIubWl4ZXJcbiAgfSlcblxuICBXZWJjYXN0ZXIubm9kZSA9IG5ldyBXZWJjYXN0ZXIuTm9kZVxuICAgIG1vZGVsOiBXZWJjYXN0ZXIuc2V0dGluZ3NcblxuICBfLmV4dGVuZCBXZWJjYXN0ZXIsXG4gICAgdmlld3M6XG4gICAgICBzZXR0aW5ncyA6IG5ldyBXZWJjYXN0ZXIuVmlldy5TZXR0aW5nc1xuICAgICAgICBtb2RlbCA6IFdlYmNhc3Rlci5zZXR0aW5nc1xuICAgICAgICBub2RlICA6IFdlYmNhc3Rlci5ub2RlXG4gICAgICAgIGVsICAgIDogJChcImRpdi5zZXR0aW5nc1wiKVxuXG4gICAgICBtaXhlcjogbmV3IFdlYmNhc3Rlci5WaWV3Lk1peGVyXG4gICAgICAgIG1vZGVsIDogV2ViY2FzdGVyLm1peGVyXG4gICAgICAgIGVsICAgIDogJChcImRpdi5taXhlclwiKVxuXG4gICAgICBtaWNyb3Bob25lOiBuZXcgV2ViY2FzdGVyLlZpZXcuTWljcm9waG9uZVxuICAgICAgICBtb2RlbDogbmV3IFdlYmNhc3Rlci5Nb2RlbC5NaWNyb3Bob25lKHtcbiAgICAgICAgICB0cmFja0dhaW4gICA6IDEwMFxuICAgICAgICAgIHBhc3NUaHJvdWdoIDogZmFsc2VcbiAgICAgICAgfSwge1xuICAgICAgICAgIG1peGVyOiBXZWJjYXN0ZXIubWl4ZXJcbiAgICAgICAgICBub2RlOiAgV2ViY2FzdGVyLm5vZGVcbiAgICAgICAgfSlcbiAgICAgICAgZWw6ICQoXCJkaXYubWljcm9waG9uZVwiKVxuXG4gICAgICBwbGF5bGlzdExlZnQgOiBuZXcgV2ViY2FzdGVyLlZpZXcuUGxheWxpc3RcbiAgICAgICAgbW9kZWwgOiBuZXcgV2ViY2FzdGVyLk1vZGVsLlBsYXlsaXN0KHtcbiAgICAgICAgICBzaWRlICAgICAgICA6IFwibGVmdFwiXG4gICAgICAgICAgZmlsZXMgICAgICAgOiBbXVxuICAgICAgICAgIGZpbGVJbmRleCAgIDogLTFcbiAgICAgICAgICB2b2x1bWVMZWZ0ICA6IDBcbiAgICAgICAgICB2b2x1bWVSaWdodCA6IDBcbiAgICAgICAgICB0cmFja0dhaW4gICA6IDEwMFxuICAgICAgICAgIHBhc3NUaHJvdWdoIDogZmFsc2VcbiAgICAgICAgICBwbGF5VGhyb3VnaCA6IHRydWVcbiAgICAgICAgICBwb3NpdGlvbiAgICA6IDAuMFxuICAgICAgICAgIGxvb3AgICAgICAgIDogZmFsc2VcbiAgICAgICAgfSwge1xuICAgICAgICAgIG1peGVyIDogV2ViY2FzdGVyLm1peGVyXG4gICAgICAgICAgbm9kZSAgOiBXZWJjYXN0ZXIubm9kZVxuICAgICAgICB9KVxuICAgICAgICBlbCA6ICQoXCJkaXYucGxheWxpc3QtbGVmdFwiKVxuXG4gICAgICBwbGF5bGlzdFJpZ2h0IDogbmV3IFdlYmNhc3Rlci5WaWV3LlBsYXlsaXN0XG4gICAgICAgIG1vZGVsIDogbmV3IFdlYmNhc3Rlci5Nb2RlbC5QbGF5bGlzdCh7XG4gICAgICAgICAgc2lkZSAgICAgICAgOiBcInJpZ2h0XCJcbiAgICAgICAgICBmaWxlcyAgICAgICA6IFtdXG4gICAgICAgICAgZmlsZUluZGV4ICAgOiAtMVxuICAgICAgICAgIHZvbHVtZUxlZnQgIDogMFxuICAgICAgICAgIHZvbHVtZVJpZ2h0IDogMFxuICAgICAgICAgIHRyYWNrR2FpbiAgIDogMTAwXG4gICAgICAgICAgcGFzc1Rocm91Z2ggOiBmYWxzZVxuICAgICAgICAgIHBsYXlUaHJvdWdoIDogdHJ1ZVxuICAgICAgICAgIHBvc2l0aW9uICAgIDogMC4wXG4gICAgICAgICAgbG9vcCAgICAgICAgOiBmYWxzZVxuICAgICAgICB9LCB7XG4gICAgICAgICAgbWl4ZXIgOiBXZWJjYXN0ZXIubWl4ZXJcbiAgICAgICAgICBub2RlICA6IFdlYmNhc3Rlci5ub2RlXG4gICAgICAgIH0pXG4gICAgICAgIGVsIDogJChcImRpdi5wbGF5bGlzdC1yaWdodFwiKVxuXG5cbiAgXy5pbnZva2UgV2ViY2FzdGVyLnZpZXdzLCBcInJlbmRlclwiXG4iLCJjbGFzcyBXZWJjYXN0ZXIuTm9kZVxuICBfLmV4dGVuZCBAcHJvdG90eXBlLCBCYWNrYm9uZS5FdmVudHNcblxuICBkZWZhdWx0Q2hhbm5lbHMgPSAyXG5cbiAgY29uc3RydWN0b3I6ICh7QG1vZGVsfSkgLT5cbiAgICBpZiB0eXBlb2Ygd2Via2l0QXVkaW9Db250ZXh0ICE9IFwidW5kZWZpbmVkXCJcbiAgICAgIEBjb250ZXh0ID0gbmV3IHdlYmtpdEF1ZGlvQ29udGV4dFxuICAgIGVsc2VcbiAgICAgIEBjb250ZXh0ID0gbmV3IEF1ZGlvQ29udGV4dFxuXG4gICAgQHdlYmNhc3QgPSBAY29udGV4dC5jcmVhdGVXZWJjYXN0U291cmNlIDQwOTYsIGRlZmF1bHRDaGFubmVsc1xuXG4gICAgQGNvbm5lY3QoKVxuXG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOnBhc3NUaHJvdWdoXCIsID0+XG4gICAgICBAd2ViY2FzdC5zZXRQYXNzVGhyb3VnaCBAbW9kZWwuZ2V0KFwicGFzc1Rocm91Z2hcIilcblxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTpjaGFubmVsc1wiLCA9PlxuICAgICAgQHJlY29ubmVjdCgpXG5cbiAgY29ubmVjdDogLT5cbiAgICBpZiBAbW9kZWwuZ2V0KFwiY2hhbm5lbHNcIikgPT0gMVxuICAgICAgQG1lcmdlciB8fD0gQGNvbnRleHQuY3JlYXRlQ2hhbm5lbE1lcmdlciBAZGVmYXVsdENoYW5uZWxzXG4gICAgICBAbWVyZ2VyLmNvbm5lY3QgQGNvbnRleHQuZGVzdGluYXRpb25cbiAgICAgIEB3ZWJjYXN0LmNvbm5lY3QgQG1lcmdlclxuICAgIGVsc2VcbiAgICAgIEB3ZWJjYXN0LmNvbm5lY3QgQGNvbnRleHQuZGVzdGluYXRpb24gICBcblxuICBkaXNjb25uZWN0OiAtPlxuICAgIEB3ZWJjYXN0LmRpc2Nvbm5lY3QoKVxuICAgIEBtZXJnZXI/LmRpc2Nvbm5lY3QoKVxuXG4gIHJlY29ubmVjdDogLT5cbiAgICBAZGlzY29ubmVjdCgpXG4gICAgQGNvbm5lY3QoKVxuXG4gIHN0YXJ0U3RyZWFtOiAtPlxuICAgIHN3aXRjaCBAbW9kZWwuZ2V0KFwiZW5jb2RlclwiKVxuICAgICAgd2hlbiBcIm1wM1wiXG4gICAgICAgIGVuY29kZXIgPSBXZWJjYXN0LkVuY29kZXIuTXAzXG4gICAgICB3aGVuIFwicmF3XCJcbiAgICAgICAgZW5jb2RlciA9IFdlYmNhc3QuRW5jb2Rlci5SYXdcblxuICAgIEBlbmNvZGVyID0gbmV3IGVuY29kZXJcbiAgICAgIGNoYW5uZWxzICAgOiBAbW9kZWwuZ2V0KFwiY2hhbm5lbHNcIilcbiAgICAgIHNhbXBsZXJhdGUgOiBAbW9kZWwuZ2V0KFwic2FtcGxlcmF0ZVwiKVxuICAgICAgYml0cmF0ZSAgICA6IEBtb2RlbC5nZXQoXCJiaXRyYXRlXCIpXG5cbiAgICBpZiBAbW9kZWwuZ2V0KFwic2FtcGxlcmF0ZVwiKSAhPSBAY29udGV4dC5zYW1wbGVSYXRlXG4gICAgICBAZW5jb2RlciA9IG5ldyBXZWJjYXN0LkVuY29kZXIuUmVzYW1wbGVcbiAgICAgICAgZW5jb2RlciAgICA6IEBlbmNvZGVyXG4gICAgICAgIHR5cGUgICAgICAgOiBTYW1wbGVyYXRlLkxJTkVBUixcbiAgICAgICAgc2FtcGxlcmF0ZSA6IEBjb250ZXh0LnNhbXBsZVJhdGVcblxuICAgIGlmIEBtb2RlbC5nZXQoXCJhc3luY2hyb25vdXNcIilcbiAgICAgIEBlbmNvZGVyID0gbmV3IFdlYmNhc3QuRW5jb2Rlci5Bc3luY2hyb25vdXNcbiAgICAgICAgZW5jb2RlciA6IEBlbmNvZGVyXG4gICAgICAgIHNjcmlwdHM6IFtcbiAgICAgICAgICBcImh0dHBzOi8vY2RuLnJhd2dpdC5jb20vd2ViY2FzdC9saWJzYW1wbGVyYXRlLmpzL21hc3Rlci9kaXN0L2xpYnNhbXBsZXJhdGUuanNcIixcbiAgICAgICAgICBcImh0dHBzOi8vY2RuLnJhd2dpdC5jb20vc2F2b25ldC9zaGluZS9tYXN0ZXIvanMvZGlzdC9saWJzaGluZS5qc1wiLFxuICAgICAgICAgIFwiaHR0cHM6Ly9jZG4ucmF3Z2l0LmNvbS93ZWJjYXN0L3dlYmNhc3QuanMvbWFzdGVyL2xpYi93ZWJjYXN0LmpzXCJcbiAgICAgICAgXVxuXG4gICAgQHdlYmNhc3QuY29ubmVjdFNvY2tldCBAZW5jb2RlciwgQG1vZGVsLmdldChcInVyaVwiKVxuXG4gIHN0b3BTdHJlYW06IC0+XG4gICAgQHdlYmNhc3QuY2xvc2UoKVxuXG4gIGNyZWF0ZUF1ZGlvU291cmNlOiAoe2ZpbGUsIGF1ZGlvfSwgbW9kZWwsIGNiKSAtPlxuICAgIGVsID0gbmV3IEF1ZGlvIFVSTC5jcmVhdGVPYmplY3RVUkwoZmlsZSlcbiAgICBlbC5jb250cm9scyA9IGZhbHNlXG4gICAgZWwuYXV0b3BsYXkgPSBmYWxzZVxuICAgIGVsLmxvb3AgICAgID0gZmFsc2VcblxuICAgIGVsLmFkZEV2ZW50TGlzdGVuZXIgXCJlbmRlZFwiLCA9PlxuICAgICAgbW9kZWwub25FbmQoKVxuXG4gICAgc291cmNlID0gbnVsbFxuXG4gICAgZWwuYWRkRXZlbnRMaXN0ZW5lciBcImNhbnBsYXlcIiwgPT5cbiAgICAgIHJldHVybiBpZiBzb3VyY2U/XG5cbiAgICAgIHNvdXJjZSA9IEBjb250ZXh0LmNyZWF0ZU1lZGlhRWxlbWVudFNvdXJjZSBlbFxuXG4gICAgICBzb3VyY2UucGxheSA9IC0+XG4gICAgICAgIGVsLnBsYXkoKVxuXG4gICAgICBzb3VyY2UucG9zaXRpb24gPSAtPlxuICAgICAgICBlbC5jdXJyZW50VGltZVxuXG4gICAgICBzb3VyY2UuZHVyYXRpb24gPSAtPlxuICAgICAgICBlbC5kdXJhdGlvblxuXG4gICAgICBzb3VyY2UucGF1c2VkID0gLT5cbiAgICAgICAgZWwucGF1c2VkXG5cbiAgICAgIHNvdXJjZS5zdG9wID0gLT5cbiAgICAgICAgZWwucGF1c2UoKVxuICAgICAgICBlbC5yZW1vdmUoKVxuXG4gICAgICBzb3VyY2UucGF1c2UgPSAtPlxuICAgICAgICBlbC5wYXVzZSgpXG5cbiAgICAgIHNvdXJjZS5zZWVrID0gKHBlcmNlbnQpIC0+XG4gICAgICAgIHRpbWUgPSBwZXJjZW50KnBhcnNlRmxvYXQoYXVkaW8ubGVuZ3RoKVxuXG4gICAgICAgIGVsLmN1cnJlbnRUaW1lID0gdGltZVxuICAgICAgICB0aW1lXG5cbiAgICAgIGNiIHNvdXJjZVxuXG4gIGNyZWF0ZUZpbGVTb3VyY2U6IChmaWxlLCBtb2RlbCwgY2IpIC0+XG4gICAgQHNvdXJjZT8uZGlzY29ubmVjdCgpXG5cbiAgICBAY3JlYXRlQXVkaW9Tb3VyY2UgZmlsZSwgbW9kZWwsIGNiXG5cbiAgY3JlYXRlTWljcm9waG9uZVNvdXJjZTogKGNvbnN0cmFpbnRzLCBjYikgLT5cbiAgICBuYXZpZ2F0b3IubWVkaWFEZXZpY2VzLmdldFVzZXJNZWRpYShjb25zdHJhaW50cykudGhlbiAoc3RyZWFtKSA9PlxuICAgICAgc291cmNlID0gQGNvbnRleHQuY3JlYXRlTWVkaWFTdHJlYW1Tb3VyY2Ugc3RyZWFtXG5cbiAgICAgIHNvdXJjZS5zdG9wID0gLT5cbiAgICAgICAgc3RyZWFtLmdldEF1ZGlvVHJhY2tzKCk/WzBdLnN0b3AoKVxuXG4gICAgICBjYiBzb3VyY2VcblxuICBzZW5kTWV0YWRhdGE6IChkYXRhKSAtPlxuICAgIEB3ZWJjYXN0LnNlbmRNZXRhZGF0YSBkYXRhXG5cbiAgY2xvc2U6IChjYikgLT5cbiAgICBAd2ViY2FzdC5jbG9zZSBjYlxuIiwid2luZG93LldlYmNhc3RlciA9IFdlYmNhc3RlciA9XG4gIFZpZXc6IHt9XG4gIE1vZGVsOiB7fVxuICBTb3VyY2U6IHt9XG5cbiAgcHJldHRpZnlUaW1lOiAodGltZSkgLT5cbiAgICBob3VycyAgID0gcGFyc2VJbnQgdGltZSAvIDM2MDBcbiAgICB0aW1lICAgJT0gMzYwMFxuICAgIG1pbnV0ZXMgPSBwYXJzZUludCB0aW1lIC8gNjBcbiAgICBzZWNvbmRzID0gcGFyc2VJbnQgdGltZSAlIDYwXG5cbiAgICBtaW51dGVzID0gXCIwI3ttaW51dGVzfVwiIGlmIG1pbnV0ZXMgPCAxMFxuICAgIHNlY29uZHMgPSBcIjAje3NlY29uZHN9XCIgaWYgc2Vjb25kcyA8IDEwXG5cbiAgICByZXN1bHQgPSBcIiN7bWludXRlc306I3tzZWNvbmRzfVwiXG4gICAgcmVzdWx0ID0gXCIje2hvdXJzfToje3Jlc3VsdH1cIiBpZiBob3VycyA+IDBcblxuICAgIHJlc3VsdFxuIiwiY2xhc3MgV2ViY2FzdGVyLlZpZXcuTWljcm9waG9uZSBleHRlbmRzIFdlYmNhc3Rlci5WaWV3LlRyYWNrXG4gIGV2ZW50czpcbiAgICBcImNsaWNrIC5yZWNvcmQtYXVkaW9cIiAgICA6IFwib25SZWNvcmRcIlxuICAgIFwiY2xpY2sgLnBhc3NUaHJvdWdoXCIgICAgIDogXCJvblBhc3NUaHJvdWdoXCJcbiAgICBcInN1Ym1pdFwiICAgICAgICAgICAgICAgICA6IFwib25TdWJtaXRcIlxuXG4gIGluaXRpYWxpemU6IC0+XG4gICAgc3VwZXIoKVxuXG4gICAgQG1vZGVsLm9uIFwicGxheWluZ1wiLCA9PlxuICAgICAgQCQoXCIucGxheS1jb250cm9sXCIpLnJlbW92ZUF0dHIgXCJkaXNhYmxlZFwiXG4gICAgICBAJChcIi5yZWNvcmQtYXVkaW9cIikuYWRkQ2xhc3MgXCJidG4tcmVjb3JkaW5nXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1sZWZ0XCIpLndpZHRoIFwiMCVcIlxuICAgICAgQCQoXCIudm9sdW1lLXJpZ2h0XCIpLndpZHRoIFwiMCVcIlxuXG4gICAgQG1vZGVsLm9uIFwic3RvcHBlZFwiLCA9PlxuICAgICAgQCQoXCIucmVjb3JkLWF1ZGlvXCIpLnJlbW92ZUNsYXNzIFwiYnRuLXJlY29yZGluZ1wiXG4gICAgICBAJChcIi52b2x1bWUtbGVmdFwiKS53aWR0aCBcIjAlXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1yaWdodFwiKS53aWR0aCBcIjAlXCJcblxuICByZW5kZXI6IC0+XG4gICAgQCQoXCIubWljcm9waG9uZS1zbGlkZXJcIikuc2xpZGVyXG4gICAgICBvcmllbnRhdGlvbjogXCJ2ZXJ0aWNhbFwiXG4gICAgICBtaW46IDBcbiAgICAgIG1heDogMTUwXG4gICAgICB2YWx1ZTogMTAwXG4gICAgICBzdG9wOiA9PlxuICAgICAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwIFwiaGlkZVwiXG4gICAgICBzbGlkZTogKGUsIHVpKSA9PlxuICAgICAgICBAbW9kZWwuc2V0IHRyYWNrR2FpbjogdWkudmFsdWVcbiAgICAgICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcCBcInNob3dcIlxuXG4gICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcFxuICAgICAgdGl0bGU6ID0+IEBtb2RlbC5nZXQgXCJ0cmFja0dhaW5cIlxuICAgICAgdHJpZ2dlcjogXCJcIlxuICAgICAgYW5pbWF0aW9uOiBmYWxzZVxuICAgICAgcGxhY2VtZW50OiBcImxlZnRcIlxuXG4gICAgbmF2aWdhdG9yLm1lZGlhRGV2aWNlcy5nZXRVc2VyTWVkaWEoe2F1ZGlvOnRydWUsIHZpZGVvOmZhbHNlfSkudGhlbiA9PlxuICAgICAgbmF2aWdhdG9yLm1lZGlhRGV2aWNlcy5lbnVtZXJhdGVEZXZpY2VzKCkudGhlbiAoZGV2aWNlcykgPT5cbiAgICAgICAgZGV2aWNlcyA9IF8uZmlsdGVyIGRldmljZXMsICh7a2luZCwgZGV2aWNlSWR9KSAtPlxuICAgICAgICAgIGtpbmQgPT0gXCJhdWRpb2lucHV0XCJcblxuICAgICAgICByZXR1cm4gaWYgXy5pc0VtcHR5IGRldmljZXNcblxuICAgICAgICAkc2VsZWN0ID0gQCQoXCIubWljcm9waG9uZS1lbnRyeSBzZWxlY3RcIilcblxuICAgICAgICBfLmVhY2ggZGV2aWNlcywgKHtsYWJlbCxkZXZpY2VJZH0pIC0+XG4gICAgICAgICAgJHNlbGVjdC5hcHBlbmQgXCI8b3B0aW9uIHZhbHVlPScje2RldmljZUlkfSc+I3tsYWJlbH08L29wdGlvbj5cIlxuXG4gICAgICAgICRzZWxlY3QuZmluZChcIm9wdGlvbjplcSgwKVwiKS5wcm9wIFwic2VsZWN0ZWRcIiwgdHJ1ZVxuXG4gICAgICAgIEBtb2RlbC5zZXQgXCJkZXZpY2VcIiwgJHNlbGVjdC52YWwoKVxuXG4gICAgICAgICRzZWxlY3Quc2VsZWN0IC0+XG4gICAgICAgICAgQG1vZGVsLnNldCBcImRldmljZVwiLCAkc2VsZWN0LnZhbCgpXG5cbiAgICAgICAgQCQoXCIubWljcm9waG9uZS1lbnRyeVwiKS5zaG93KClcblxuICAgIHRoaXNcblxuICBvblJlY29yZDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICBpZiBAbW9kZWwuaXNQbGF5aW5nKClcbiAgICAgIHJldHVybiBAbW9kZWwuc3RvcCgpXG5cbiAgICBAJChcIi5wbGF5LWNvbnRyb2xcIikuYXR0ciBkaXNhYmxlZDogXCJkaXNhYmxlZFwiXG4gICAgQG1vZGVsLnBsYXkoKVxuIiwiY2xhc3MgV2ViY2FzdGVyLlZpZXcuTWl4ZXIgZXh0ZW5kcyBCYWNrYm9uZS5WaWV3XG4gIHJlbmRlcjogLT5cbiAgICBAJChcIi5zbGlkZXJcIikuc2xpZGVyXG4gICAgICBzdG9wOiA9PlxuICAgICAgICBAJChcImEudWktc2xpZGVyLWhhbmRsZVwiKS50b29sdGlwIFwiaGlkZVwiXG4gICAgICBzbGlkZTogKGUsIHVpKSA9PlxuICAgICAgICBAbW9kZWwuc2V0IHNsaWRlcjogdWkudmFsdWVcbiAgICAgICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcCBcInNob3dcIlxuXG4gICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcFxuICAgICAgdGl0bGU6ID0+IEBtb2RlbC5nZXQgXCJzbGlkZXJcIlxuICAgICAgdHJpZ2dlcjogXCJcIlxuICAgICAgYW5pbWF0aW9uOiBmYWxzZVxuICAgICAgcGxhY2VtZW50OiBcImJvdHRvbVwiXG5cbiAgICB0aGlzXG4iLCJjbGFzcyBXZWJjYXN0ZXIuVmlldy5QbGF5bGlzdCBleHRlbmRzIFdlYmNhc3Rlci5WaWV3LlRyYWNrXG4gIGV2ZW50czpcbiAgICBcImNsaWNrIC5wbGF5LWF1ZGlvXCIgICAgICA6IFwib25QbGF5XCJcbiAgICBcImNsaWNrIC5wYXVzZS1hdWRpb1wiICAgICA6IFwib25QYXVzZVwiXG4gICAgXCJjbGljayAucHJldmlvdXNcIiAgICAgICAgOiBcIm9uUHJldmlvdXNcIlxuICAgIFwiY2xpY2sgLm5leHRcIiAgICAgICAgICAgIDogXCJvbk5leHRcIlxuICAgIFwiY2xpY2sgLnN0b3BcIiAgICAgICAgICAgIDogXCJvblN0b3BcIlxuICAgIFwiY2xpY2sgLnByb2dyZXNzLXNlZWtcIiAgIDogXCJvblNlZWtcIlxuICAgIFwiY2xpY2sgLnBhc3NUaHJvdWdoXCIgICAgIDogXCJvblBhc3NUaHJvdWdoXCJcbiAgICBcImNoYW5nZSAuZmlsZXNcIiAgICAgICAgICA6IFwib25GaWxlc1wiXG4gICAgXCJjaGFuZ2UgLnBsYXlUaHJvdWdoXCIgICAgOiBcIm9uUGxheVRocm91Z2hcIlxuICAgIFwiY2hhbmdlIC5sb29wXCIgICAgICAgICAgIDogXCJvbkxvb3BcIlxuICAgIFwic3VibWl0XCIgICAgICAgICAgICAgICAgIDogXCJvblN1Ym1pdFwiXG5cbiAgaW5pdGlhbGl6ZTogLT5cbiAgICBzdXBlcigpXG5cbiAgICBAbW9kZWwub24gXCJjaGFuZ2U6ZmlsZUluZGV4XCIsID0+XG4gICAgICBAJChcIi50cmFjay1yb3dcIikucmVtb3ZlQ2xhc3MgXCJzdWNjZXNzXCJcbiAgICAgIEAkKFwiLnRyYWNrLXJvdy0je0Btb2RlbC5nZXQoXCJmaWxlSW5kZXhcIil9XCIpLmFkZENsYXNzIFwic3VjY2Vzc1wiXG5cbiAgICBAbW9kZWwub24gXCJwbGF5aW5nXCIsID0+XG4gICAgICBAJChcIi5wbGF5LWNvbnRyb2xcIikucmVtb3ZlQXR0ciBcImRpc2FibGVkXCJcbiAgICAgIEAkKFwiLnBsYXktYXVkaW9cIikuaGlkZSgpXG4gICAgICBAJChcIi5wYXVzZS1hdWRpb1wiKS5zaG93KClcbiAgICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uLXRleHRcIikucmVtb3ZlQ2xhc3MoXCJibGlua1wiKS50ZXh0IFwiXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1sZWZ0XCIpLndpZHRoIFwiMCVcIlxuICAgICAgQCQoXCIudm9sdW1lLXJpZ2h0XCIpLndpZHRoIFwiMCVcIlxuXG4gICAgICBpZiBAbW9kZWwuZ2V0KFwiZHVyYXRpb25cIilcbiAgICAgICAgQCQoXCIucHJvZ3Jlc3Mtdm9sdW1lXCIpLmNzcyBcImN1cnNvclwiLCBcInBvaW50ZXJcIlxuICAgICAgZWxzZVxuICAgICAgICBAJChcIi50cmFjay1wb3NpdGlvblwiKS5hZGRDbGFzcyhcInByb2dyZXNzLXN0cmlwZWQgYWN0aXZlXCIpXG4gICAgICAgIEBzZXRUcmFja1Byb2dyZXNzIDEwMFxuXG4gICAgQG1vZGVsLm9uIFwicGF1c2VkXCIsID0+XG4gICAgICBAJChcIi5wbGF5LWF1ZGlvXCIpLnNob3coKVxuICAgICAgQCQoXCIucGF1c2UtYXVkaW9cIikuaGlkZSgpXG4gICAgICBAJChcIi52b2x1bWUtbGVmdFwiKS53aWR0aCBcIjAlXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1yaWdodFwiKS53aWR0aCBcIjAlXCJcbiAgICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uLXRleHRcIikuYWRkQ2xhc3MgXCJibGlua1wiXG5cbiAgICBAbW9kZWwub24gXCJzdG9wcGVkXCIsID0+XG4gICAgICBAJChcIi5wbGF5LWF1ZGlvXCIpLnNob3coKVxuICAgICAgQCQoXCIucGF1c2UtYXVkaW9cIikuaGlkZSgpXG4gICAgICBAJChcIi5wcm9ncmVzcy12b2x1bWVcIikuY3NzIFwiY3Vyc29yXCIsIFwiXCJcbiAgICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uXCIpLnJlbW92ZUNsYXNzKFwicHJvZ3Jlc3Mtc3RyaXBlZCBhY3RpdmVcIilcbiAgICAgIEBzZXRUcmFja1Byb2dyZXNzIDBcbiAgICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uLXRleHRcIikucmVtb3ZlQ2xhc3MoXCJibGlua1wiKS50ZXh0IFwiXCJcbiAgICAgIEAkKFwiLnZvbHVtZS1sZWZ0XCIpLndpZHRoIFwiMCVcIlxuICAgICAgQCQoXCIudm9sdW1lLXJpZ2h0XCIpLndpZHRoIFwiMCVcIlxuXG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOnBvc2l0aW9uXCIsID0+XG4gICAgICByZXR1cm4gdW5sZXNzIGR1cmF0aW9uID0gQG1vZGVsLmdldChcImR1cmF0aW9uXCIpXG5cbiAgICAgIHBvc2l0aW9uID0gcGFyc2VGbG9hdCBAbW9kZWwuZ2V0KFwicG9zaXRpb25cIilcblxuICAgICAgQHNldFRyYWNrUHJvZ3Jlc3MgMTAwLjAqcG9zaXRpb24vcGFyc2VGbG9hdChkdXJhdGlvbilcblxuICAgICAgQCQoXCIudHJhY2stcG9zaXRpb24tdGV4dFwiKS5cbiAgICAgICAgdGV4dCBcIiN7V2ViY2FzdGVyLnByZXR0aWZ5VGltZShwb3NpdGlvbil9IC8gI3tXZWJjYXN0ZXIucHJldHRpZnlUaW1lKGR1cmF0aW9uKX1cIlxuXG4gIHJlbmRlcjogLT5cbiAgICBAJChcIi52b2x1bWUtc2xpZGVyXCIpLnNsaWRlclxuICAgICAgb3JpZW50YXRpb246IFwidmVydGljYWxcIlxuICAgICAgbWluOiAwXG4gICAgICBtYXg6IDE1MFxuICAgICAgdmFsdWU6IDEwMFxuICAgICAgc3RvcDogPT5cbiAgICAgICAgQCQoXCJhLnVpLXNsaWRlci1oYW5kbGVcIikudG9vbHRpcCBcImhpZGVcIlxuICAgICAgc2xpZGU6IChlLCB1aSkgPT5cbiAgICAgICAgQG1vZGVsLnNldCB0cmFja0dhaW46IHVpLnZhbHVlXG4gICAgICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXAgXCJzaG93XCJcblxuICAgIEAkKFwiYS51aS1zbGlkZXItaGFuZGxlXCIpLnRvb2x0aXBcbiAgICAgIHRpdGxlOiA9PiBAbW9kZWwuZ2V0IFwidHJhY2tHYWluXCJcbiAgICAgIHRyaWdnZXI6IFwiXCJcbiAgICAgIGFuaW1hdGlvbjogZmFsc2VcbiAgICAgIHBsYWNlbWVudDogXCJsZWZ0XCJcblxuICAgIGZpbGVzID0gQG1vZGVsLmdldCBcImZpbGVzXCJcblxuICAgIEAkKFwiLmZpbGVzLXRhYmxlXCIpLmVtcHR5KClcblxuICAgIHJldHVybiB0aGlzIHVubGVzcyBmaWxlcy5sZW5ndGggPiAwXG5cbiAgICBfLmVhY2ggZmlsZXMsICh7ZmlsZSwgYXVkaW8sIG1ldGFkYXRhfSwgaW5kZXgpID0+XG4gICAgICBpZiBhdWRpbz8ubGVuZ3RoICE9IDBcbiAgICAgICAgdGltZSA9IFdlYmNhc3Rlci5wcmV0dGlmeVRpbWUgYXVkaW8ubGVuZ3RoXG4gICAgICBlbHNlXG4gICAgICAgIHRpbWUgPSBcIk4vQVwiXG5cbiAgICAgIGlmIEBtb2RlbC5nZXQoXCJmaWxlSW5kZXhcIikgPT0gaW5kZXhcbiAgICAgICAga2xhc3MgPSBcInN1Y2Nlc3NcIlxuICAgICAgZWxzZVxuICAgICAgICBrbGFzcyA9IFwiXCJcbiAgICAgICAgXG4gICAgICBAJChcIi5maWxlcy10YWJsZVwiKS5hcHBlbmQgXCJcIlwiXG4gICAgICAgIDx0ciBjbGFzcz0ndHJhY2stcm93IHRyYWNrLXJvdy0je2luZGV4fSAje2tsYXNzfSc+XG4gICAgICAgICAgPHRkPiN7aW5kZXgrMX08L3RkPlxuICAgICAgICAgIDx0ZD4je21ldGFkYXRhPy50aXRsZSB8fCBcIlVua25vd24gVGl0bGVcIn08L3RkPlxuICAgICAgICAgIDx0ZD4je21ldGFkYXRhPy5hcnRpc3QgfHwgXCJVbmtub3duIEFydGlzdFwifTwvdGQ+XG4gICAgICAgICAgPHRkPiN7dGltZX08L3RkPlxuICAgICAgICA8L3RyPlxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBcIlwiXCJcblxuICAgIEAkKFwiLnBsYXlsaXN0LXRhYmxlXCIpLnNob3coKVxuXG4gICAgdGhpc1xuXG4gIHNldFRyYWNrUHJvZ3Jlc3M6IChwZXJjZW50KSAtPlxuICAgIEAkKFwiLnRyYWNrLXBvc2l0aW9uXCIpLndpZHRoIFwiI3twZXJjZW50KiQoXCIucHJvZ3Jlc3Mtdm9sdW1lXCIpLndpZHRoKCkvMTAwfXB4XCJcbiAgICBAJChcIi50cmFjay1wb3NpdGlvbi10ZXh0LC5wcm9ncmVzcy1zZWVrXCIpLndpZHRoICQoXCIucHJvZ3Jlc3Mtdm9sdW1lXCIpLndpZHRoKClcblxuICBwbGF5OiAob3B0aW9ucykgLT5cbiAgICBAbW9kZWwuc3RvcCgpXG4gICAgcmV0dXJuIHVubGVzcyBAZmlsZSA9IEBtb2RlbC5zZWxlY3RGaWxlIG9wdGlvbnNcblxuICAgIEAkKFwiLnBsYXktY29udHJvbFwiKS5hdHRyIGRpc2FibGVkOiBcImRpc2FibGVkXCJcbiAgICBAbW9kZWwucGxheSBAZmlsZVxuXG4gIG9uUGxheTogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG4gICAgaWYgQG1vZGVsLmlzUGxheWluZygpXG4gICAgICBAbW9kZWwudG9nZ2xlUGF1c2UoKVxuICAgICAgcmV0dXJuXG5cbiAgICBAcGxheSgpXG5cbiAgb25QYXVzZTogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG4gICAgQG1vZGVsLnRvZ2dsZVBhdXNlKClcblxuICBvblByZXZpb3VzOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcbiAgICByZXR1cm4gdW5sZXNzIEBtb2RlbC5pc1BsYXlpbmcoKT9cblxuICAgIEBwbGF5IGJhY2t3YXJkOiB0cnVlXG5cbiAgb25OZXh0OiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcbiAgICByZXR1cm4gdW5sZXNzIEBtb2RlbC5pc1BsYXlpbmcoKVxuXG4gICAgQHBsYXkoKVxuXG4gIG9uU3RvcDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICBAJChcIi50cmFjay1yb3dcIikucmVtb3ZlQ2xhc3MgXCJzdWNjZXNzXCJcbiAgICBAbW9kZWwuc3RvcCgpXG4gICAgQGZpbGUgPSBudWxsXG5cbiAgb25TZWVrOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcblxuICAgIEBtb2RlbC5zZWVrICgoZS5wYWdlWCAtICQoZS50YXJnZXQpLm9mZnNldCgpLmxlZnQpIC8gJChlLnRhcmdldCkud2lkdGgoKSlcblxuICBvbkZpbGVzOiAtPlxuICAgIGZpbGVzID0gQCQoXCIuZmlsZXNcIilbMF0uZmlsZXNcbiAgICBAJChcIi5maWxlc1wiKS5hdHRyIGRpc2FibGVkOiBcImRpc2FibGVkXCJcblxuICAgIEBtb2RlbC5hcHBlbmRGaWxlcyBmaWxlcywgPT5cbiAgICAgIEAkKFwiLmZpbGVzXCIpLnJlbW92ZUF0dHIoXCJkaXNhYmxlZFwiKS52YWwgXCJcIlxuICAgICAgQHJlbmRlcigpXG5cbiAgb25QbGF5VGhyb3VnaDogKGUpIC0+XG4gICAgQG1vZGVsLnNldCBwbGF5VGhyb3VnaDogJChlLnRhcmdldCkuaXMoXCI6Y2hlY2tlZFwiKVxuXG4gIG9uTG9vcDogKGUpIC0+XG4gICAgQG1vZGVsLnNldCBsb29wOiAkKGUudGFyZ2V0KS5pcyhcIjpjaGVja2VkXCIpXG4iLCJjbGFzcyBXZWJjYXN0ZXIuVmlldy5TZXR0aW5ncyBleHRlbmRzIEJhY2tib25lLlZpZXdcbiAgZXZlbnRzOlxuICAgIFwiY2hhbmdlIC51cmlcIiAgICAgICAgICAgIDogXCJvblVyaVwiXG4gICAgXCJjaGFuZ2UgaW5wdXQuZW5jb2RlclwiICAgOiBcIm9uRW5jb2RlclwiXG4gICAgXCJjaGFuZ2UgaW5wdXQuY2hhbm5lbHNcIiAgOiBcIm9uQ2hhbm5lbHNcIlxuICAgIFwiY2hhbmdlIC5zYW1wbGVyYXRlXCIgICAgIDogXCJvblNhbXBsZXJhdGVcIlxuICAgIFwiY2hhbmdlIC5iaXRyYXRlXCIgICAgICAgIDogXCJvbkJpdHJhdGVcIlxuICAgIFwiY2hhbmdlIC5hc3luY2hyb25vdXNcIiAgIDogXCJvbkFzeW5jaHJvbm91c1wiXG4gICAgXCJjbGljayAucGFzc1Rocm91Z2hcIiAgICAgOiBcIm9uUGFzc1Rocm91Z2hcIlxuICAgIFwiY2xpY2sgLnN0YXJ0LXN0cmVhbVwiICAgIDogXCJvblN0YXJ0XCJcbiAgICBcImNsaWNrIC5zdG9wLXN0cmVhbVwiICAgICA6IFwib25TdG9wXCJcbiAgICBcImNsaWNrIC51cGRhdGUtbWV0YWRhdGFcIiA6IFwib25NZXRhZGF0YVVwZGF0ZVwiXG4gICAgXCJzdWJtaXRcIiAgICAgICAgICAgICAgICAgOiBcIm9uU3VibWl0XCJcblxuICBpbml0aWFsaXplOiAoe0Bub2RlfSkgLT5cbiAgICBAbW9kZWwub24gXCJjaGFuZ2U6cGFzc1Rocm91Z2hcIiwgPT5cbiAgICAgIGlmIEBtb2RlbC5nZXQoXCJwYXNzVGhyb3VnaFwiKVxuICAgICAgICBAJChcIi5wYXNzVGhyb3VnaFwiKS5hZGRDbGFzcyhcImJ0bi1jdWVkXCIpLnJlbW92ZUNsYXNzIFwiYnRuLWluZm9cIlxuICAgICAgZWxzZVxuICAgICAgICBAJChcIi5wYXNzVGhyb3VnaFwiKS5hZGRDbGFzcyhcImJ0bi1pbmZvXCIpLnJlbW92ZUNsYXNzIFwiYnRuLWN1ZWRcIlxuXG4gIHJlbmRlcjogLT5cbiAgICBzYW1wbGVyYXRlID0gQG1vZGVsLmdldCBcInNhbXBsZXJhdGVcIlxuICAgIEAkKFwiLnNhbXBsZXJhdGVcIikuZW1wdHkoKVxuICAgIF8uZWFjaCBAbW9kZWwuZ2V0KFwic2FtcGxlcmF0ZXNcIiksIChyYXRlKSA9PlxuICAgICAgc2VsZWN0ZWQgPSBpZiBzYW1wbGVyYXRlID09IHJhdGUgdGhlbiBcInNlbGVjdGVkXCIgZWxzZSBcIlwiXG4gICAgICAkKFwiPG9wdGlvbiB2YWx1ZT0nI3tyYXRlfScgI3tzZWxlY3RlZH0+I3tyYXRlfTwvb3B0aW9uPlwiKS5cbiAgICAgICAgYXBwZW5kVG8gQCQoXCIuc2FtcGxlcmF0ZVwiKVxuXG4gICAgYml0cmF0ZSA9IEBtb2RlbC5nZXQgXCJiaXRyYXRlXCJcbiAgICBAJChcIi5iaXRyYXRlXCIpLmVtcHR5KClcbiAgICBfLmVhY2ggQG1vZGVsLmdldChcImJpdHJhdGVzXCIpLCAocmF0ZSkgPT5cbiAgICAgIHNlbGVjdGVkID0gaWYgYml0cmF0ZSA9PSByYXRlIHRoZW4gXCJzZWxlY3RlZFwiIGVsc2UgXCJcIlxuICAgICAgJChcIjxvcHRpb24gdmFsdWU9JyN7cmF0ZX0nICN7c2VsZWN0ZWR9PiN7cmF0ZX08L29wdGlvbj5cIikuXG4gICAgICAgIGFwcGVuZFRvIEAkKFwiLmJpdHJhdGVcIilcblxuICAgIHRoaXNcblxuICBvblVyaTogLT5cbiAgICBAbW9kZWwuc2V0IHVyaTogQCQoXCIudXJpXCIpLnZhbCgpXG5cbiAgb25FbmNvZGVyOiAoZSkgLT5cbiAgICBAbW9kZWwuc2V0IGVuY29kZXI6ICQoZS50YXJnZXQpLnZhbCgpXG5cbiAgb25DaGFubmVsczogKGUpIC0+XG4gICAgQG1vZGVsLnNldCBjaGFubmVsczogcGFyc2VJbnQoJChlLnRhcmdldCkudmFsKCkpXG5cbiAgb25TYW1wbGVyYXRlOiAoZSkgLT5cbiAgICBAbW9kZWwuc2V0IHNhbXBsZXJhdGU6IHBhcnNlSW50KCQoZS50YXJnZXQpLnZhbCgpKVxuXG4gIG9uQml0cmF0ZTogKGUpIC0+XG4gICAgQG1vZGVsLnNldCBiaXRyYXRlOiBwYXJzZUludCgkKGUudGFyZ2V0KS52YWwoKSlcblxuICBvbkFzeW5jaHJvbm91czogKGUpIC0+XG4gICAgQG1vZGVsLnNldCBhc3luY2hyb25vdXM6ICQoZS50YXJnZXQpLmlzKFwiOmNoZWNrZWRcIilcblxuICBvblBhc3NUaHJvdWdoOiAoZSkgLT5cbiAgICBlLnByZXZlbnREZWZhdWx0KClcblxuICAgIEBtb2RlbC50b2dnbGVQYXNzVGhyb3VnaCgpXG5cbiAgb25TdGFydDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICBAJChcIi5zdG9wLXN0cmVhbVwiKS5zaG93KClcbiAgICBAJChcIi5zdGFydC1zdHJlYW1cIikuaGlkZSgpXG4gICAgQCQoXCJpbnB1dCwgc2VsZWN0XCIpLmF0dHIgZGlzYWJsZWQ6IFwiZGlzYWJsZWRcIlxuICAgIEAkKFwiLm1hbnVhbC1tZXRhZGF0YSwgLnVwZGF0ZS1tZXRhZGF0YVwiKS5yZW1vdmVBdHRyIFwiZGlzYWJsZWRcIlxuXG4gICAgQG5vZGUuc3RhcnRTdHJlYW0oKVxuXG4gIG9uU3RvcDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICBAJChcIi5zdG9wLXN0cmVhbVwiKS5oaWRlKClcbiAgICBAJChcIi5zdGFydC1zdHJlYW1cIikuc2hvdygpXG4gICAgQCQoXCJpbnB1dCwgc2VsZWN0XCIpLnJlbW92ZUF0dHIgXCJkaXNhYmxlZFwiXG4gICAgQCQoXCIubWFudWFsLW1ldGFkYXRhLCAudXBkYXRlLW1ldGFkYXRhXCIpLmF0dHIgZGlzYWJsZWQ6IFwiZGlzYWJsZWRcIlxuXG4gICAgQG5vZGUuc3RvcFN0cmVhbSgpXG5cbiAgb25NZXRhZGF0YVVwZGF0ZTogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG5cbiAgICB0aXRsZSA9IEAkKFwiLm1hbnVhbC1tZXRhZGF0YS5hcnRpc3RcIikudmFsKClcbiAgICBhcnRpc3QgPSBAJChcIi5tYW51YWwtbWV0YWRhdGEudGl0bGVcIikudmFsKClcblxuICAgIHJldHVybiB1bmxlc3MgYXJ0aXN0ICE9IFwiXCIgfHwgdGl0bGUgIT0gXCJcIlxuXG4gICAgQG5vZGUuc2VuZE1ldGFkYXRhXG4gICAgICBhcnRpc3Q6IGFydGlzdFxuICAgICAgdGl0bGU6ICB0aXRsZVxuXG4gICAgQCQoXCIubWV0YWRhdGEtdXBkYXRlZFwiKS5zaG93IDQwMCwgPT5cbiAgICAgY2IgPSA9PlxuICAgICAgIEAkKFwiLm1ldGFkYXRhLXVwZGF0ZWRcIikuaGlkZSA0MDBcblxuICAgICBzZXRUaW1lb3V0IGNiLCAyMDAwXG5cbiAgb25TdWJtaXQ6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuIiwiY2xhc3MgV2ViY2FzdGVyLlZpZXcuVHJhY2sgZXh0ZW5kcyBCYWNrYm9uZS5WaWV3XG4gIGluaXRpYWxpemU6IC0+XG4gICAgQG1vZGVsLm9uIFwiY2hhbmdlOnBhc3NUaHJvdWdoXCIsID0+XG4gICAgICBpZiBAbW9kZWwuZ2V0KFwicGFzc1Rocm91Z2hcIilcbiAgICAgICAgQCQoXCIucGFzc1Rocm91Z2hcIikuYWRkQ2xhc3MoXCJidG4tY3VlZFwiKS5yZW1vdmVDbGFzcyBcImJ0bi1pbmZvXCJcbiAgICAgIGVsc2VcbiAgICAgICAgQCQoXCIucGFzc1Rocm91Z2hcIikuYWRkQ2xhc3MoXCJidG4taW5mb1wiKS5yZW1vdmVDbGFzcyBcImJ0bi1jdWVkXCJcblxuICAgIEBtb2RlbC5vbiBcImNoYW5nZTp2b2x1bWVMZWZ0XCIsID0+XG4gICAgICBAJChcIi52b2x1bWUtbGVmdFwiKS53aWR0aCBcIiN7QG1vZGVsLmdldChcInZvbHVtZUxlZnRcIil9JVwiXG5cbiAgICBAbW9kZWwub24gXCJjaGFuZ2U6dm9sdW1lUmlnaHRcIiwgPT5cbiAgICAgIEAkKFwiLnZvbHVtZS1yaWdodFwiKS53aWR0aCBcIiN7QG1vZGVsLmdldChcInZvbHVtZVJpZ2h0XCIpfSVcIlxuXG4gIG9uUGFzc1Rocm91Z2g6IChlKSAtPlxuICAgIGUucHJldmVudERlZmF1bHQoKVxuXG4gICAgQG1vZGVsLnRvZ2dsZVBhc3NUaHJvdWdoKClcblxuICBvblN1Ym1pdDogKGUpIC0+XG4gICAgZS5wcmV2ZW50RGVmYXVsdCgpXG4iLCJjbGFzcyBXZWJjYXN0ZXIuTW9kZWwuTWljcm9waG9uZSBleHRlbmRzIFdlYmNhc3Rlci5Nb2RlbC5UcmFja1xuICBpbml0aWFsaXplOiAtPlxuICAgIHN1cGVyKClcblxuICAgIEBvbiBcImNoYW5nZTpkZXZpY2VcIiwgLT5cbiAgICAgIHJldHVybiB1bmxlc3MgQHNvdXJjZT9cbiAgICAgIEBjcmVhdGVTb3VyY2UoKVxuXG4gIGNyZWF0ZVNvdXJjZTogKGNiKSAtPlxuICAgIEBzb3VyY2UuZGlzY29ubmVjdCBAZGVzdGluYXRpb24gaWYgQHNvdXJjZT9cblxuICAgIGNvbnN0cmFpbnRzID0ge3ZpZGVvOmZhbHNlfVxuXG4gICAgaWYgQGdldChcImRldmljZVwiKVxuICAgICAgY29uc3RyYWludHMuYXVkaW8gPVxuICAgICAgICBleGFjdDogQGdldChcImRldmljZVwiKVxuICAgIGVsc2VcbiAgICAgIGNvbnN0cmFpbnRzLmF1ZGlvID0gdHJ1ZVxuXG4gICAgQG5vZGUuY3JlYXRlTWljcm9waG9uZVNvdXJjZSBjb25zdHJhaW50cywgKEBzb3VyY2UpID0+XG4gICAgICBAc291cmNlLmNvbm5lY3QgQGRlc3RpbmF0aW9uXG4gICAgICBjYj8oKVxuXG4gIHBsYXk6IC0+XG4gICAgQHByZXBhcmUoKVxuXG4gICAgQGNyZWF0ZVNvdXJjZSA9PlxuICAgICAgQHRyaWdnZXIgXCJwbGF5aW5nXCJcbiIsImNsYXNzIFdlYmNhc3Rlci5Nb2RlbC5NaXhlciBleHRlbmRzIEJhY2tib25lLk1vZGVsXG4gIGdldFZvbHVtZTogKHBvc2l0aW9uKSAtPlxuICAgIGlmIHBvc2l0aW9uIDwgMC41XG4gICAgICByZXR1cm4gMipwb3NpdGlvblxuXG4gICAgMVxuXG4gIGdldFNsaWRlcjogLT5cbiAgICBwYXJzZUZsb2F0KEBnZXQoXCJzbGlkZXJcIikpLzEwMC4wMFxuXG4gIGdldExlZnRWb2x1bWU6IC0+XG4gICAgQGdldFZvbHVtZSgxLjAgLSBAZ2V0U2xpZGVyKCkpXG4gICAgXG4gIGdldFJpZ2h0Vm9sdW1lOiAtPlxuICAgIEBnZXRWb2x1bWUgQGdldFNsaWRlcigpXG4iLCJjbGFzcyBXZWJjYXN0ZXIuTW9kZWwuUGxheWxpc3QgZXh0ZW5kcyBXZWJjYXN0ZXIuTW9kZWwuVHJhY2tcbiAgaW5pdGlhbGl6ZTogLT5cbiAgICBzdXBlcigpXG5cbiAgICBAbWl4ZXIub24gXCJjaGFuZ2U6c2xpZGVyXCIsIEBzZXRNaXhHYWluXG5cbiAgICBAbWl4R2FpbiA9IEBub2RlLmNvbnRleHQuY3JlYXRlR2FpbigpXG4gICAgQG1peEdhaW4uY29ubmVjdCBAbm9kZS53ZWJjYXN0XG5cbiAgICBAc2luayA9IEBtaXhHYWluXG5cbiAgc2V0TWl4R2FpbjogPT5cbiAgICByZXR1cm4gdW5sZXNzIEBtaXhHYWluP1xuXG4gICAgaWYgQGdldChcInNpZGVcIikgPT0gXCJsZWZ0XCJcbiAgICAgIEBtaXhHYWluLmdhaW4udmFsdWUgPSBAbWl4ZXIuZ2V0TGVmdFZvbHVtZSgpXG4gICAgZWxzZVxuICAgICAgQG1peEdhaW4uZ2Fpbi52YWx1ZSA9IEBtaXhlci5nZXRSaWdodFZvbHVtZSgpXG5cbiAgYXBwZW5kRmlsZXM6IChuZXdGaWxlcywgY2IpIC0+XG4gICAgZmlsZXMgPSBAZ2V0IFwiZmlsZXNcIlxuXG4gICAgb25Eb25lID0gXy5hZnRlciBuZXdGaWxlcy5sZW5ndGgsID0+XG4gICAgICBAc2V0IGZpbGVzOiBmaWxlc1xuICAgICAgY2I/KClcblxuICAgIGFkZEZpbGUgPSAoZmlsZSkgLT5cbiAgICAgIGZpbGUucmVhZFRhZ2xpYk1ldGFkYXRhIChkYXRhKSA9PlxuICAgICAgICBmaWxlcy5wdXNoXG4gICAgICAgICAgZmlsZSAgICAgOiBmaWxlXG4gICAgICAgICAgYXVkaW8gICAgOiBkYXRhLmF1ZGlvXG4gICAgICAgICAgbWV0YWRhdGEgOiBkYXRhLm1ldGFkYXRhXG5cbiAgICAgICAgb25Eb25lKClcblxuICAgIGFkZEZpbGUgbmV3RmlsZXNbaV0gZm9yIGkgaW4gWzAuLm5ld0ZpbGVzLmxlbmd0aC0xXVxuXG4gIHNlbGVjdEZpbGU6IChvcHRpb25zID0ge30pIC0+XG4gICAgZmlsZXMgPSBAZ2V0IFwiZmlsZXNcIlxuICAgIGluZGV4ID0gQGdldCBcImZpbGVJbmRleFwiXG5cbiAgICByZXR1cm4gaWYgZmlsZXMubGVuZ3RoID09IDBcblxuICAgIGluZGV4ICs9IGlmIG9wdGlvbnMuYmFja3dhcmQgdGhlbiAtMSBlbHNlIDFcblxuICAgIGluZGV4ID0gZmlsZXMubGVuZ3RoLTEgaWYgaW5kZXggPCAwXG5cbiAgICBpZiBpbmRleCA+PSBmaWxlcy5sZW5ndGhcbiAgICAgIHVubGVzcyBAZ2V0KFwibG9vcFwiKVxuICAgICAgICBAc2V0IGZpbGVJbmRleDogLTFcbiAgICAgICAgcmV0dXJuXG5cbiAgICAgIGlmIGluZGV4IDwgMFxuICAgICAgICBpbmRleCA9IGZpbGVzLmxlbmd0aC0xXG4gICAgICBlbHNlXG4gICAgICAgIGluZGV4ID0gMFxuXG4gICAgZmlsZSA9IGZpbGVzW2luZGV4XVxuICAgIEBzZXQgZmlsZUluZGV4OiBpbmRleFxuXG4gICAgZmlsZVxuXG4gIHBsYXk6IChmaWxlKSAtPlxuICAgIEBwcmVwYXJlKClcblxuICAgIEBzZXRNaXhHYWluKClcblxuICAgIEBub2RlLmNyZWF0ZUZpbGVTb3VyY2UgZmlsZSwgdGhpcywgKEBzb3VyY2UpID0+XG4gICAgICBAc291cmNlLmNvbm5lY3QgQGRlc3RpbmF0aW9uXG5cbiAgICAgIGlmIEBzb3VyY2UuZHVyYXRpb24/XG4gICAgICAgIEBzZXQgZHVyYXRpb246IEBzb3VyY2UuZHVyYXRpb24oKVxuICAgICAgZWxzZVxuICAgICAgICBAc2V0IGR1cmF0aW9uOiBwYXJzZUZsb2F0KGZpbGUuYXVkaW8ubGVuZ3RoKSBpZiBmaWxlLmF1ZGlvPy5sZW5ndGg/XG5cbiAgICAgIEBzb3VyY2UucGxheSBmaWxlXG4gICAgICBAdHJpZ2dlciBcInBsYXlpbmdcIlxuXG4gIG9uRW5kOiAtPlxuICAgIEBzdG9wKClcblxuICAgIEBwbGF5IEBzZWxlY3RGaWxlKCkgaWYgQGdldChcInBsYXlUaHJvdWdoXCIpXG4iLCJjbGFzcyBXZWJjYXN0ZXIuTW9kZWwuU2V0dGluZ3MgZXh0ZW5kcyBCYWNrYm9uZS5Nb2RlbFxuICBpbml0aWFsaXplOiAoYXR0cmlidXRlcywgb3B0aW9ucykgLT5cbiAgICBAbWl4ZXIgPSBvcHRpb25zLm1peGVyXG5cbiAgICBAbWl4ZXIub24gXCJjdWVcIiwgPT5cbiAgICAgIEBzZXQgcGFzc1Rocm91Z2g6IGZhbHNlXG5cbiAgdG9nZ2xlUGFzc1Rocm91Z2g6IC0+XG4gICAgcGFzc1Rocm91Z2ggPSBAZ2V0KFwicGFzc1Rocm91Z2hcIilcbiAgICBpZiBwYXNzVGhyb3VnaFxuICAgICAgQHNldCBwYXNzVGhyb3VnaDogZmFsc2VcbiAgICBlbHNlXG4gICAgICBAbWl4ZXIudHJpZ2dlciBcImN1ZVwiXG4gICAgICBAc2V0IHBhc3NUaHJvdWdoOiB0cnVlXG4iLCJjbGFzcyBXZWJjYXN0ZXIuTW9kZWwuVHJhY2sgZXh0ZW5kcyBCYWNrYm9uZS5Nb2RlbFxuICBpbml0aWFsaXplOiAoYXR0cmlidXRlcywgb3B0aW9ucykgLT5cbiAgICBAbm9kZSA9IG9wdGlvbnMubm9kZVxuICAgIEBtaXhlciA9IG9wdGlvbnMubWl4ZXJcblxuICAgIEBtaXhlci5vbiBcImN1ZVwiLCA9PlxuICAgICAgQHNldCBwYXNzVGhyb3VnaDogZmFsc2VcblxuICAgIEBvbiBcImNoYW5nZTp0cmFja0dhaW5cIiwgQHNldFRyYWNrR2FpblxuICAgIEBvbiBcImVuZGVkXCIsIEBzdG9wXG5cbiAgICBAc2luayA9IEBub2RlLndlYmNhc3RcblxuICB0b2dnbGVQYXNzVGhyb3VnaDogLT5cbiAgICBwYXNzVGhyb3VnaCA9IEBnZXQoXCJwYXNzVGhyb3VnaFwiKVxuICAgIGlmIHBhc3NUaHJvdWdoXG4gICAgICBAc2V0IHBhc3NUaHJvdWdoOiBmYWxzZVxuICAgIGVsc2VcbiAgICAgIEBtaXhlci50cmlnZ2VyIFwiY3VlXCJcbiAgICAgIEBzZXQgcGFzc1Rocm91Z2g6IHRydWVcblxuICBpc1BsYXlpbmc6IC0+XG4gICAgQHNvdXJjZT9cblxuICBjcmVhdGVDb250cm9sc05vZGU6IC0+XG4gICAgYnVmZmVyU2l6ZSA9IDQwOTZcbiAgICBidWZmZXJMZW5ndGggPSBwYXJzZUZsb2F0KGJ1ZmZlclNpemUpL3BhcnNlRmxvYXQoQG5vZGUuY29udGV4dC5zYW1wbGVSYXRlKVxuXG4gICAgYnVmZmVyTG9nID0gTWF0aC5sb2cgcGFyc2VGbG9hdChidWZmZXJTaXplKVxuICAgIGxvZzEwICAgICA9IDIuMCAqIE1hdGgubG9nKDEwKVxuXG4gICAgc291cmNlID0gQG5vZGUuY29udGV4dC5jcmVhdGVTY3JpcHRQcm9jZXNzb3IgYnVmZmVyU2l6ZSwgMiwgMlxuXG4gICAgc291cmNlLm9uYXVkaW9wcm9jZXNzID0gKGJ1ZikgPT5cbiAgICAgIHJldCA9IHt9XG5cbiAgICAgIGlmIEBzb3VyY2U/LnBvc2l0aW9uP1xuICAgICAgICByZXRbXCJwb3NpdGlvblwiXSA9IEBzb3VyY2UucG9zaXRpb24oKVxuICAgICAgZWxzZVxuICAgICAgICBpZiBAc291cmNlP1xuICAgICAgICAgIHJldFtcInBvc2l0aW9uXCJdID0gcGFyc2VGbG9hdChAZ2V0KFwicG9zaXRpb25cIikpK2J1ZmZlckxlbmd0aFxuXG4gICAgICBmb3IgY2hhbm5lbCBpbiBbMC4uYnVmLmlucHV0QnVmZmVyLm51bWJlck9mQ2hhbm5lbHMtMV1cbiAgICAgICAgY2hhbm5lbERhdGEgPSBidWYuaW5wdXRCdWZmZXIuZ2V0Q2hhbm5lbERhdGEgY2hhbm5lbFxuXG4gICAgICAgIHJtcyA9IDAuMFxuICAgICAgICBmb3IgaSBpbiBbMC4uY2hhbm5lbERhdGEubGVuZ3RoLTFdXG4gICAgICAgICAgcm1zICs9IE1hdGgucG93IGNoYW5uZWxEYXRhW2ldLCAyXG4gICAgICAgIHZvbHVtZSA9IDEwMCpNYXRoLmV4cCgoTWF0aC5sb2cocm1zKS1idWZmZXJMb2cpL2xvZzEwKVxuXG4gICAgICAgIGlmIGNoYW5uZWwgPT0gMFxuICAgICAgICAgIHJldFtcInZvbHVtZUxlZnRcIl0gPSB2b2x1bWVcbiAgICAgICAgZWxzZVxuICAgICAgICAgIHJldFtcInZvbHVtZVJpZ2h0XCJdID0gdm9sdW1lXG5cbiAgICAgICAgQHNldCByZXRcblxuICAgICAgICBidWYub3V0cHV0QnVmZmVyLmdldENoYW5uZWxEYXRhKGNoYW5uZWwpLnNldCBjaGFubmVsRGF0YVxuXG4gICAgc291cmNlXG5cbiAgY3JlYXRlUGFzc1Rocm91Z2g6IC0+XG4gICAgc291cmNlID0gQG5vZGUuY29udGV4dC5jcmVhdGVTY3JpcHRQcm9jZXNzb3IgMjU2LCAyLCAyXG5cbiAgICBzb3VyY2Uub25hdWRpb3Byb2Nlc3MgPSAoYnVmKSA9PlxuICAgICAgY2hhbm5lbERhdGEgPSBidWYuaW5wdXRCdWZmZXIuZ2V0Q2hhbm5lbERhdGEgY2hhbm5lbFxuXG4gICAgICBmb3IgY2hhbm5lbCBpbiBbMC4uYnVmLmlucHV0QnVmZmVyLm51bWJlck9mQ2hhbm5lbHMtMV1cbiAgICAgICAgaWYgQGdldChcInBhc3NUaHJvdWdoXCIpXG4gICAgICAgICAgYnVmLm91dHB1dEJ1ZmZlci5nZXRDaGFubmVsRGF0YShjaGFubmVsKS5zZXQgY2hhbm5lbERhdGFcbiAgICAgICAgZWxzZVxuICAgICAgICAgIGJ1Zi5vdXRwdXRCdWZmZXIuZ2V0Q2hhbm5lbERhdGEoY2hhbm5lbCkuc2V0IChuZXcgRmxvYXQzMkFycmF5IGNoYW5uZWxEYXRhLmxlbmd0aClcblxuICAgIHNvdXJjZVxuXG4gIHNldFRyYWNrR2FpbjogPT5cbiAgICByZXR1cm4gdW5sZXNzIEB0cmFja0dhaW4/XG4gICAgQHRyYWNrR2Fpbi5nYWluLnZhbHVlID0gcGFyc2VGbG9hdChAZ2V0KFwidHJhY2tHYWluXCIpKS8xMDAuMFxuXG4gIHByZXBhcmU6IC0+XG4gICAgQGNvbnRyb2xzTm9kZSA9IEBjcmVhdGVDb250cm9sc05vZGUoKVxuICAgIEBjb250cm9sc05vZGUuY29ubmVjdCBAc2lua1xuXG4gICAgQHRyYWNrR2FpbiA9IEBub2RlLmNvbnRleHQuY3JlYXRlR2FpbigpXG4gICAgQHRyYWNrR2Fpbi5jb25uZWN0IEBjb250cm9sc05vZGVcbiAgICBAc2V0VHJhY2tHYWluKClcblxuICAgIEBkZXN0aW5hdGlvbiA9IEB0cmFja0dhaW5cblxuICAgIEBwYXNzVGhyb3VnaCA9IEBjcmVhdGVQYXNzVGhyb3VnaCgpXG4gICAgQHBhc3NUaHJvdWdoLmNvbm5lY3QgQG5vZGUuY29udGV4dC5kZXN0aW5hdGlvblxuICAgIEBkZXN0aW5hdGlvbi5jb25uZWN0IEBwYXNzVGhyb3VnaFxuXG5cbiAgdG9nZ2xlUGF1c2U6IC0+XG4gICAgcmV0dXJuIHVubGVzcyBAc291cmNlPy5wYXVzZT9cblxuICAgIGlmIEBzb3VyY2U/LnBhdXNlZD8oKVxuICAgICAgQHNvdXJjZS5wbGF5KClcbiAgICAgIEB0cmlnZ2VyIFwicGxheWluZ1wiXG4gICAgZWxzZVxuICAgICAgQHNvdXJjZS5wYXVzZSgpXG4gICAgICBAdHJpZ2dlciBcInBhdXNlZFwiXG5cbiAgc3RvcDogLT5cbiAgICBAc291cmNlPy5zdG9wPygpXG4gICAgQHNvdXJjZT8uZGlzY29ubmVjdCgpXG4gICAgQHRyYWNrR2Fpbj8uZGlzY29ubmVjdCgpXG4gICAgQGNvbnRyb2xzTm9kZT8uZGlzY29ubmVjdCgpXG4gICAgQHBhc3NUaHJvdWdoPy5kaXNjb25uZWN0KClcblxuICAgIEBzb3VyY2UgPSBAdHJhY2tHYWluID0gQGNvbnRyb2xzTm9kZSA9IEBwYXNzVGhyb3VnaCA9IG51bGxcblxuICAgIEBzZXQgcG9zaXRpb246IDAuMFxuICAgIEB0cmlnZ2VyIFwic3RvcHBlZFwiXG5cbiAgc2VlazogKHBlcmNlbnQpIC0+XG4gICAgcmV0dXJuIHVubGVzcyBwb3NpdGlvbiA9IEBzb3VyY2U/LnNlZWs/KHBlcmNlbnQpXG5cbiAgICBAc2V0IHBvc2l0aW9uOiBwb3NpdGlvblxuXG4gIHNlbmRNZXRhZGF0YTogKGZpbGUpIC0+XG4gICAgQG5vZGUuc2VuZE1ldGFkYXRhIGZpbGUubWV0YWRhdGFcbiJdfQ==
