import {computed, ref, watch} from "vue";
import {useInjectWebDjNode} from "~/components/Public/WebDJ/useWebDjNode";

export function useWebDjTrack() {
    const {
        context,
        sink,
        channelCount,
        bufferSize
    } = useInjectWebDjNode();

    const trackGain = ref(55);
    const trackPassThrough = ref(false);
    const position = ref(null);
    const volume = ref(0);

    const source = ref(null);

    const createControlsNode = () => {
        const bufferLog = Math.log(Number(bufferSize.value));
        const log10 = 2.0 * Math.log(10);

        const newSource = context.value.createScriptProcessor(
            bufferSize.value,
            channelCount.value,
            channelCount.value
        );

        newSource.onaudioprocess = (buf) => {
            if (typeof (source.value?.position) === "function") {
                position.value = source.value.position();
            }

            for (let channel = 0; channel < buf.inputBuffer.numberOfChannels; channel++) {
                const channelData = buf.inputBuffer.getChannelData(channel);

                let rms = 0.0;
                for (let i = 0; i < channelData.length; i++) {
                    rms += Math.pow(channelData[i], 2);
                }

                volume.value = 100 * Math.exp((Math.log(rms) - bufferLog) / log10);

                buf.outputBuffer.getChannelData(channel).set(channelData);
            }
        };

        return newSource;
    };

    const createPassThrough = () => {
        const newSource = context.value.createScriptProcessor(
            bufferSize.value,
            channelCount.value,
            channelCount.value
        );

        newSource.onaudioprocess = (buf) => {
            for (let channel = 0; channel < buf.inputBuffer.numberOfChannels; channel++) {
                const channelData = buf.inputBuffer.getChannelData(channel);

                if (trackPassThrough.value) {
                    buf.outputBuffer.getChannelData(channel).set(channelData);
                } else {
                    buf.outputBuffer.getChannelData(channel).set(new Float32Array(channelData.length));
                }
            }
        };

        return newSource;
    };

    let controlsNode = null;
    let trackGainNode = null;
    let passThroughNode = null;

    const setTrackGain = (newGain) => {
        if (null === trackGainNode) {
            return;
        }

        trackGainNode.gain.value = parseFloat(newGain) / 100.0;
    };
    watch(trackGain, setTrackGain);

    const prepare = () => {
        controlsNode = createControlsNode();
        controlsNode.connect(sink.value);

        trackGainNode = context.value.createGain();
        trackGainNode.gain.value = Number(trackGain.value) / 100.0;
        trackGainNode.connect(controlsNode);

        passThroughNode = createPassThrough();
        passThroughNode.connect(context.value.destination);
        trackGainNode.connect(passThroughNode);

        context.value.resume();

        return trackGainNode;
    }

    const isPlaying = computed(() => {
        return source.value !== null;
    });

    const isPaused = computed(() => {
        return (source.value !== null)
            ? source.value.paused()
            : false;
    });

    const togglePause = () => {
        if (source.value === null) {
            return;
        }

        if (source.value.paused()) {
            source.value.play();
        } else {
            source.value.pause();
        }
    };

    const stop = () => {
        source.value?.stop();
        source.value?.disconnect();

        trackGainNode?.disconnect();
        controlsNode?.disconnect();
        passThroughNode?.disconnect();

        source.value = trackGainNode = controlsNode = passThroughNode = null;

        position.value = 0.0;
    };

    return {
        source,
        trackGain,
        trackPassThrough,
        position,
        volume,
        isPlaying,
        isPaused,
        prepare,
        togglePause,
        stop,
    };
}
