<template>
    <div class="waveform-controls">
        <div class="row">
            <div class="col-md-12">
                <div id="waveform_container">
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
            <div
                v-if="showVolume"
                class="col-md-5"
            >
                <div class="inline-volume-controls d-flex align-items-center">
                    <div class="flex-shrink-0 mx-2">
                        <mute-button
                            class="p-0"
                            :volume="volume"
                            :is-muted="isMuted"
                            @toggle-mute="toggleMute"
                        />
                    </div>
                    <div class="flex-fill mx-1">
                        <input
                            v-model.number="volume"
                            type="range"
                            :title="$gettext('Volume')"
                            class="player-volume-range form-range w-100"
                            min="0"
                            max="100"
                            step="1"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import WaveSurfer from "wavesurfer.js";
import timelinePlugin from "wavesurfer.js/dist/plugins/timeline.js";
import regionsPlugin, {RegionParams} from "wavesurfer.js/dist/plugins/regions.js";
import {onMounted, onUnmounted, ref, toRef, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import MuteButton from "~/components/Common/Audio/MuteButton.vue";
import {usePlayerStore} from "~/functions/usePlayerStore.ts";
import {storeToRefs} from "pinia";

const props = withDefaults(
    defineProps<{
        regions?: RegionParams[],
        audioUrl: string,
        waveformUrl: string,
        waveformCacheUrl?: string,
    }>(),
    {
        regions: () => [],
    }
);

let wavesurfer: WaveSurfer | null = null;
let wsRegions: regionsPlugin | null = null;

const playerStore = usePlayerStore();
const {showVolume, volume, logVolume, isMuted} = storeToRefs(playerStore);
const {toggle, toggleMute, setDuration, setCurrentTime} = playerStore;

const zoom = ref(0);
watch(zoom, (val) => {
    wavesurfer?.zoom(val);
});

watch(logVolume, (val) => {
    wavesurfer?.setVolume(val);
});

watch(isMuted, (val) => {
    wavesurfer?.setMuted(val);
});

const isExternalJson = ref(false);

const {axiosSilent} = useAxios();

const cacheWaveformRemotely = () => {
    if (!props.waveformCacheUrl) {
        return;
    }

    const decodedData = wavesurfer?.getDecodedData() ?? null;
    const peaks = wavesurfer?.exportPeaks() ?? null;

    if (decodedData === null || peaks === null) {
        return;
    }

    const dataToCache = {
        source: 'wavesurfer',
        channels: decodedData.numberOfChannels,
        sample_rate: decodedData.sampleRate,
        length: decodedData.length,
        data: peaks
    };

    void axiosSilent.post(props.waveformCacheUrl, dataToCache);
};

onMounted(async () => {
    wavesurfer = WaveSurfer.create({
        container: '#waveform_container',
        waveColor: '#2196f3',
        progressColor: '#4081CF',
    });

    wavesurfer.registerPlugin(timelinePlugin.create());

    wsRegions = wavesurfer.registerPlugin(regionsPlugin.create());

    wavesurfer.on('ready', (newDuration: number) => {
        // Disable any other players.
        toggle();

        wavesurfer?.setVolume(logVolume.value);

        if (!isExternalJson.value) {
            cacheWaveformRemotely();
        }

        setDuration(newDuration);
    });

    wavesurfer.on('decode', (newDuration: number) => {
        setDuration(newDuration);
    });

    wavesurfer.on('timeupdate', (newTime: number) => {
        setCurrentTime(newTime);
    });

    try {
        const {data} = await axiosSilent.get(props.waveformUrl);
        const waveformJson = data?.data ?? null;

        if (waveformJson) {
            isExternalJson.value = true;
            await wavesurfer?.load(props.audioUrl, waveformJson);
        } else {
            isExternalJson.value = false;
            await wavesurfer?.load(props.audioUrl);
        }
    } catch {
        isExternalJson.value = false;
        await wavesurfer?.load(props.audioUrl);
    }
});

watch(
    toRef(props, 'regions'),
    (regions: RegionParams[]) => {
        wsRegions?.clearRegions();

        regions.forEach((region) => {
            wsRegions?.addRegion(
                {
                    resize: false,
                    drag: false,
                    ...region,
                }
            );
        });
    },
);

onUnmounted(() => {
    wavesurfer = null;
});

const play = () => {
    void wavesurfer?.play();
};

const stop = () => {
    wavesurfer?.pause();
};

defineExpose({
    play,
    stop
})
</script>

<style lang="scss">
#waveform_container {
    border: 1px solid var(--bs-tertiary-bg);
    border-radius: 4px;
}
</style>
