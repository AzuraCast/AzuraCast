var stream = {};
var defaultChannels;

// Define the streaming radio context.
if (typeof webkitAudioContext !== "undefined") {
    stream.context = new webkitAudioContext;
} else {
    stream.context = new AudioContext;
}

stream.webcast = this.stream.context.createWebcastSource(4096, defaultChannels);
stream.webcast.connect(this.stream.context.destination);

stream.createAudioSource = function({file, audio}, model, cb) {
    var el, source;

    el = new Audio(URL.createObjectURL(file));
    el.controls = false;
    el.autoplay = false;
    el.loop = false;

    el.addEventListener("ended", function() {
        return model.onEnd();
    });

    source = null;
    return el.addEventListener("canplay", function() {
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
};

stream.createFileSource = function(file, model, cb) {
    var ref;
    if ((ref = this.source) != null) {
        ref.disconnect();
    }
    return this.createAudioSource(file, model, cb);
};

stream.createMicrophoneSource = function(constraints, cb) {
    return navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
        var source;

        source = this.context.createMediaStreamSource(stream);
        source.stop = function() {
            var ref;
            return (ref = stream.getAudioTracks()) != null ? ref[0].stop() : void 0;
        };
        return cb(source);
    });
};

stream.close = function(cb) {
    return this.webcast.close(cb);
};

export default stream;
