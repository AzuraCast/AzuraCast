import {computed, inject, provide, ref} from "vue";

const injectKey = "webDjNode";

export function useInjectWebDjNode() {
    return inject(injectKey);
}

export function useProvideWebDjNode(node) {
    provide(injectKey, node);
}

export function useWebDjNode(webcaster) {
    const {connect: connectSocket} = webcaster;

    const doPassThrough = ref(false);

    const bitrate = ref(128);
    const sampleRate = ref(44100);
    const channelCount = ref(2);
    const bufferSize = ref(256);

    const context = computed(() => {
        return new AudioContext({
            sampleRate: sampleRate.value
        });
    });

    const sink = computed(() => {
        const currentContext = context.value;

        const sink = currentContext.createScriptProcessor(
            bufferSize.value,
            channelCount.value,
            channelCount.value
        );

        sink.onaudioprocess = (buf) => {
            for (let channel = 0; channel < buf.inputBuffer.numberOfChannels; channel++) {
                const channelData = buf.inputBuffer.getChannelData(channel);
                buf.outputBuffer.getChannelData(channel).set(channelData);
            }
        };

        return sink;
    });

    const passThrough = computed(() => {
        const currentContext = context.value;

        const passThrough = currentContext.createScriptProcessor(
            bufferSize.value,
            channelCount.value,
            channelCount.value
        );

        passThrough.onaudioprocess = (buf) => {
            for (let channel = 0; channel < buf.inputBuffer.numberOfChannels; channel++) {
                const channelData = buf.inputBuffer.getChannelData(channel);

                if (doPassThrough.value) {
                    buf.outputBuffer.getChannelData(channel).set(channelData);
                } else {
                    buf.outputBuffer.getChannelData(channel).set(new Float32Array(channelData.length));
                }
            }
        };

        sink.value.connect(passThrough);
        passThrough.value.connect(currentContext.destination);

        return passThrough;
    });

    const streamNode = computed(() => {
        const currentContext = context.value;

        const streamNode = currentContext.createMediaStreamDestination();
        streamNode.channelCount = channelCount.value;

        sink.value.connect(streamNode);

        return streamNode;
    });

    let mediaRecorder;

    const startStream = (username = null, password = null) => {
        context.value.resume();

        mediaRecorder = new MediaRecorder(
            streamNode.value.stream,
            {
                mimeType: "audio/webm;codecs=opus",
                audioBitsPerSecond: bitrate.value * 1000
            }
        );

        connectSocket(mediaRecorder, username, password);

        mediaRecorder.start(1000);
    }

    const stopStream = () => {
        mediaRecorder?.stop();
    };

    return {
        doPassThrough,
        bitrate,
        sampleRate,
        channelCount,
        bufferSize,
        context,
        sink,
        passThrough,
        streamNode,
        startStream,
        stopStream
    };
}
