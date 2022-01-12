var stream = {};
var defaultChannels = 2;

// Function to be called upon the first user interaction.
stream.init = function () {
  // Define the streaming radio context.
  if (!this.context) {
    if (typeof webkitAudioContext !== 'undefined') {
      this.context = new webkitAudioContext;
    } else {
      this.context = new AudioContext;
    }

    this.webcast = this.context.createWebcastSource(4096, defaultChannels);
    this.webcast.connect(this.context.destination);
  }
};

stream.resumeContext = function () {
  if (this.context.state !== 'running') {
    this.context.resume();
  }
};

stream.createAudioSource = function ({
                                       file,
                                       audio
                                     }, model, cb) {
  var el,
    source;

  el = new Audio(URL.createObjectURL(file));
  el.controls = false;
  el.autoplay = false;
  el.loop = false;

  el.addEventListener('ended', function () {
    return model.onEnd();
  });

  source = null;
  return el.addEventListener('canplay', function () {
    if (source != null) {
      return;
    }

    source = stream.context.createMediaElementSource(el);
    source.play = function () {
      return el.play();
    };
    source.position = function () {
      return el.currentTime;
    };
    source.duration = function () {
      return el.duration;
    };
    source.paused = function () {
      return el.paused;
    };
    source.stop = function () {
      el.pause();
      return el.remove();
    };
    source.pause = function () {
      return el.pause();
    };
    source.seek = function (percent) {
      var time;
      time = percent * parseFloat(audio.length);
      el.currentTime = time;
      return time;
    };

    return cb(source);
  });
};

stream.createFileSource = function (file, model, cb) {
  var ref;
  if ((ref = this.source) != null) {
    ref.disconnect();
  }
  return this.createAudioSource(file, model, cb);
};

stream.createMicrophoneSource = function (constraints, cb) {
  return navigator.mediaDevices.getUserMedia(constraints).then(function (bit_stream) {
    var source;

    source = stream.context.createMediaStreamSource(bit_stream);
    source.stop = function () {
      var ref;
      return (ref = bit_stream.getAudioTracks()) != null ? ref[0].stop() : void 0;
    };
    return cb(source);
  });
};

stream.close = function (cb) {
  return this.webcast.close(cb);
};

export default stream;
