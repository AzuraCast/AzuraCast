<template>
    <div class="waveform-controls">
        <div class="row">
            <div class="col-md-12">
                <div class="waveform__container">
                    <div id="waveform-timeline" />
                    <div id="waveform" />
                </div>
            </div>
        </div>
        <div class="row mt-3 align-items-center">
            <div class="col-md-7">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <label for="waveform-zoom">
                            {{ $gettext('Waveform Zoom') }}
                        </label>
                    </div>
                    <div class="flex-fill mx-3">
                        <input
                            id="waveform-zoom"
                            v-model.number="zoom"
                            type="range"
                            min="0"
                            max="256"
                            class="w-100"
                        >
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="inline-volume-controls d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <button
                            class="btn btn-sm btn-outline-inverse"
                            :title="$gettext('Mute')"
                            @click="volume = 0"
                        >
                            <icon icon="volume_mute" />
                        </button>
                    </div>
                    <div class="flex-fill mx-1">
                        <input
                            v-model.number="volume"
                            type="range"
                            :title="$gettext('Volume')"
                            class="player-volume-range custom-range w-100"
                            min="0"
                            max="100"
                            step="1"
                        >
                    </div>
                    <div class="flex-shrink-0">
                        <button
                            class="btn btn-sm btn-outline-inverse"
                            :title="$gettext('Full Volume')"
                            @click="volume = 100"
                        >
                            <icon icon="volume_up" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import WS from 'wavesurfer.js';
import timeline from 'wavesurfer.js/dist/plugin/wavesurfer.timeline.js';
import regions from 'wavesurfer.js/dist/plugin/wavesurfer.regions.js';
import getLogarithmicVolume from '~/functions/getLogarithmicVolume.js';
import Icon from './Icon';
import {useStorage} from "@vueuse/core";
import {onMounted, onUnmounted, ref, watch} from "vue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    audioUrl: {
        type: String,
        required: true
    },
    waveformUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['ready']);

let wavesurfer = null;

const volume = useStorage('player_volume', 55);
const zoom = ref(0);

watch(zoom, (val) => {
    wavesurfer?.zoom(val);
});

watch(volume, (val) => {
    wavesurfer?.setVolume(getLogarithmicVolume(val));
});

const {axios} = useAxios();

onMounted(() => {
    wavesurfer = WS.create({
        backend: 'MediaElement',
        container: '#waveform',
        waveColor: '#2196f3',
        progressColor: '#4081CF',
        plugins: [
            timeline.create({
                container: '#waveform-timeline',
                primaryColor: '#222',
                secondaryColor: '#888',
                primaryFontColor: '#222',
                secondaryFontColor: '#888'
            }),
            regions.create({
                regions: []
            })
        ]
    });

    wavesurfer.on('ready', () => {
        wavesurfer.setVolume(getLogarithmicVolume(volume.value));

        emit('ready');
    });

    axios.get(props.waveformUrl).then((resp) => {
        let waveformJson = resp?.data?.data ?? null;
        if (waveformJson) {
            wavesurfer.load(props.audioUrl, waveformJson);
        } else {
            wavesurfer.load(props.audioUrl);
        }
    }).catch((err) => {
        console.error(err);
        wavesurfer.load(props.audioUrl);
    });
});

onUnmounted(() => {
    wavesurfer = null;
});

const play = () => {
    wavesurfer?.play();
};

const stop = () => {
    wavesurfer?.pause();
};

const getCurrentTime = () => {
    return wavesurfer?.getCurrentTime();
};

const getDuration = () => {
    return wavesurfer?.getDuration();
}

const addRegion = (start, end, color) => {
    wavesurfer?.addRegion(
        {
            start: start,
            end: end,
            resize: false,
            drag: false,
            color: color
        }
    );
};

const clearRegions = () => {
    wavesurfer?.clearRegions();
}

defineExpose({
    play,
    stop,
    getCurrentTime,
    getDuration,
    addRegion,
    clearRegions
})
</script>
