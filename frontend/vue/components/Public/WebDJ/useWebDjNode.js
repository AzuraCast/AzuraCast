import {ref} from "vue";
import Webcast from "~/vendor/webcast/webcast";

export function useWebDjNode() {
    const doPlayThrough = ref(false);
    const isStreaming = ref(false);

    const context = new AudioContext({
        sampleRate: 44100
    });

    const sink = context.createScriptProcessor(256, 2, 2);

    sink.onaudioprocess((buf) => {
        let channel;
        let channelData = buf.inputBuffer.getChannelData(channel);

        for (channel = 0; channel < buf.inputBuffer.numberOfChannels - 1; channel++) {
            buf.outputBuffer.getChannelData(channel).set(channelData);
        }
    });

    const playThrough = context.createScriptProcessor(256, 2, 2);

    playThrough.onaudioprocess((buf) => {
        let channel;
        let channelData = buf.inputBuffer.getChannelData(channel);

        for (channel = 0; channel < buf.inputBuffer.numberOfChannels - 1; channel++) {
            if (doPlayThrough.value) {
                buf.outputBuffer.getChannelData(channel).set(channelData);
            } else {
                buf.outputBuffer.getChannelData(channel).set(new Float32Array(channelData.length));
            }
        }
    });

    sink.connect(playThrough);
    playThrough.connect(context.destination);

    const streamNode = context.createMediaStreamDestination();
    streamNode.channelCount = 2;

    sink.connect(streamNode);

    let socket;
    let mediaRecorder;

    const startStream = (url) => {
        isStreaming.value = true;

        context.resume();

        mediaRecorder = new MediaRecorder(
            streamNode.stream,
            {
                mimeType: "audio/webm;codecs=opus",
                audioBitsPerSecond: 128
            }
        );

        socket = new Webcast.Socket(
            mediaRecorder,
            {
                url: url
            }
        );

        mediaRecorder.start(1000);
    }

    const stopStream = () => {
        mediaRecorder?.stop();
        isStreaming.value = false;
    };

    const createAudioSource = ({file, audio}, model, cb) => {
        const el = new Audio(URL.createObjectURL(file));
        el.controls = false;
        el.autoplay = false;
        el.loop = false;

        let source = null;

        el.addEventListener("canplay", () => {
            if (source) {
                return;
            }

            source = context.createMediaElementSource(el);

            source.play = () => el.play()
            source.position = () => el.currentTime;
            source.duration = () => el.duration;
            source.paused = () => el.paused;
            source.stop = () => {
                el.pause();
                return el.remove();
            };
            source.pause = () => el.pause;
            source.seek = (percent) => {
                let time = percent * parseFloat(audio.length);
                el.currentTime = time;
                return time;
            };

            return cb(source);
        });
    };

    const createFileSource = (file, model, cb) => {
        source?.disconnect();

        return createAudioSource(file, model, cb);
    };

    const createMicrophoneSource = (constraints, cb) => {
        navigator.mediaDevices.getUserMedia(constraints).then((stream) => {
            let source = context.createMediaStreamSource(stream);
            source.stop = () => {
                let ref = stream.getAudioTracks();
                return (ref !== null)
                    ? ref[0].stop()
                    : 0;
            }

            return cb(source);
        });
    };

    const sendMetadata = (data) => {
        socket?.sendMetadata(data);
    };

    return {
        context,
        sink,
        doPlayThrough,
        playThrough,
        streamNode,
        startStream,
        stopStream,
        createAudioSource,
        createFileSource,
        createMicrophoneSource,
        sendMetadata
    };
}
