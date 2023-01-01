import {ref} from "vue";
import {useUserMedia} from "@vueuse/core";

export function useWebDjNode(webcaster) {
    const {isConnected, connect: connectSocket, metadata, sendMetadata} = webcaster;

    const doPassThrough = ref(false);

    const context = new AudioContext({
        sampleRate: 44100
    });

    const sink = context.createScriptProcessor(256, 2, 2);

    sink.onaudioprocess = (buf) => {
        for (let channel = 0; channel < buf.inputBuffer.numberOfChannels; channel++) {
            let channelData = buf.inputBuffer.getChannelData(channel);
            buf.outputBuffer.getChannelData(channel).set(channelData);
        }
    };

    const passThrough = context.createScriptProcessor(256, 2, 2);

    passThrough.onaudioprocess = (buf) => {
        for (let channel = 0; channel < buf.inputBuffer.numberOfChannels; channel++) {
            let channelData = buf.inputBuffer.getChannelData(channel);

            if (doPassThrough.value) {
                buf.outputBuffer.getChannelData(channel).set(channelData);
            } else {
                buf.outputBuffer.getChannelData(channel).set(new Float32Array(channelData.length));
            }
        }
    };

    sink.connect(passThrough);
    passThrough.connect(context.destination);

    const streamNode = context.createMediaStreamDestination();
    streamNode.channelCount = 2;

    sink.connect(streamNode);

    let mediaRecorder;

    const startStream = (username = null, password = null) => {
        context.resume();

        mediaRecorder = new MediaRecorder(
            streamNode.stream,
            {
                mimeType: "audio/webm;codecs=opus",
                audioBitsPerSecond: 128 * 1000
            }
        );

        connectSocket(mediaRecorder, username, password);

        mediaRecorder.start(1000);
    }

    const stopStream = () => {
        mediaRecorder?.stop();
    };

    const createAudioSource = ({file, audio}, cb, onEnd) => {
        const el = new Audio(URL.createObjectURL(file));
        el.controls = false;
        el.autoplay = false;
        el.loop = false;

        let source = null;

        el.addEventListener("ended", () => {
            if (typeof onEnd === "function") {
                onEnd();
            }
        });

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

    const createFileSource = (file, cb, onEnd) => {
        return createAudioSource(file, cb, onEnd);
    };

    const createMicrophoneSource = (audioDeviceId, cb) => {
        const {stream} = useUserMedia({
            audioDeviceId: audioDeviceId,
        });

        stream.stop = () => {
            let ref = stream.getAudioTracks();
            return (ref !== null)
                ? ref[0].stop()
                : 0;
        }

        return cb(stream);
    };

    return {
        doPassThrough,
        isConnected,
        context,
        sink,
        passThrough,
        streamNode,
        startStream,
        stopStream,
        createAudioSource,
        createFileSource,
        createMicrophoneSource,
        metadata,
        sendMetadata
    };
}
