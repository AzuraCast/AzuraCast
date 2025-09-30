<template>
    <p>
        {{
            $gettext('Set cue and fade points using the visual editor. The timestamps will be saved to the corresponding fields in the advanced playback settings.')
        }}
    </p>

    <waveform-component
        ref="$waveform"
        :regions="regions"
        :audio-url="audioUrl"
        :waveform-url="waveformUrl"
        :waveform-cache-url="waveformCacheUrl"
    />

    <div class="buttons mt-3">
        <div class="btn-group btn-group-sm">
            <button
                type="button"
                class="btn btn-light"
                :title="$gettext('Play')"
                @click="playAudio"
            >
                <icon-ic-play-circle/>
            </button>
            <button
                type="button"
                class="btn btn-dark"
                :title="$gettext('Stop')"
                @click="stopAudio"
            >
                <icon-ic-stop/>
            </button>
        </div>
        <div class="btn-group btn-group-sm">
            <button
                type="button"
                class="btn btn-primary"
                @click="setCueIn"
            >
                {{ $gettext('Set Cue In') }}
            </button>
            <button
                type="button"
                class="btn btn-primary"
                @click="setCueOut"
            >
                {{ $gettext('Set Cue Out') }}
            </button>
        </div>
        <div class="btn-group btn-group-sm">
            <button
                type="button"
                class="btn btn-warning"
                @click="setFadeStartNext"
            >
                {{ $gettext('Set Fade Start Next') }}
            </button>
        </div>
        <div class="btn-group btn-group-sm">
            <button
                type="button"
                class="btn btn-danger"
                @click="setFadeIn"
            >
                {{ $gettext('Set Fade In') }}
            </button>
            <button
                type="button"
                class="btn btn-danger"
                @click="setFadeOut"
            >
                {{ $gettext('Set Fade Out') }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import WaveformComponent from "~/components/Common/Audio/Waveform.vue";
import {shallowRef, useTemplateRef, watch} from "vue";
import {reactiveComputed} from "@vueuse/core";
import {RegionParams} from "wavesurfer.js/dist/plugins/regions.js";
import {storeToRefs} from "pinia";
import {usePlayerStore} from "~/functions/usePlayerStore.ts";
import {StationMediaMetadata, StationMediaRecord} from "~/entities/StationMedia.ts";
import IconIcPlayCircle from "~icons/ic/baseline-play-circle";
import IconIcStop from "~icons/ic/baseline-stop";

const props = defineProps<{
    duration: number,
    audioUrl: string,
    waveformUrl: string,
    waveformCacheUrl?: string,
}>();

const form = defineModel<StationMediaRecord>('form', {
    required: true
});

const $waveform = useTemplateRef('$waveform');

const {
    duration: durationRef,
    currentTime: currentTimeRef
} = storeToRefs(usePlayerStore());

const regions = shallowRef<RegionParams[]>([]);

const cueValues = reactiveComputed(() => {
    const formValue: StationMediaMetadata = form.value?.extra_metadata ?? {
        amplify: null,
        cue_in: null,
        cue_out: null,
        cross_start_next: null,
        fade_in: null,
        fade_out: null
    };
    const duration = durationRef.value ?? props.duration ?? 0;

    return {
        cue_in: formValue.cue_in ?? 0,
        cue_out: formValue.cue_out ?? duration,
        cross_start_next: formValue.cross_start_next ?? 0,
        fade_in: formValue.fade_in ?? 0,
        fade_out: formValue.fade_out ?? 0,
    };
});

const playAudio = () => {
    $waveform.value?.play();
};

const stopAudio = () => {
    $waveform.value?.stop();
};

const updateRegions = () => {
    const {cue_in, cue_out, cross_start_next, fade_in, fade_out} = cueValues;
    const newRegions: RegionParams[] = [];

    // Create cue region
    newRegions.push({
        start: cue_in,
        end: cue_out,
        color: 'hsla(207,90%,54%,0.4)'
    });

    // Create fade start next region
    if (cross_start_next > cue_in) {
        newRegions.push({
            start: cross_start_next,
            end: cue_out,
            color: 'hsla(29,100%,48%,0.4)'
        });
    }

    // Create fade regions
    if (fade_in) {
        newRegions.push({
            start: cue_in,
            end: fade_in + cue_in,
            color: 'hsla(351,100%,48%,0.4)'
        });
    }

    if (fade_out) {
        newRegions.push({
            start: cue_out - fade_out,
            end: cue_out,
            color: 'hsla(351,100%,48%,0.4)'
        });
    }

    regions.value = newRegions;
};

watch(cueValues, () => {
    updateRegions()
}, {
    immediate: true
});

const waveformToFloat = (value: number) => Math.round((value) * 10) / 10;

const setCueIn = () => {
    form.value.extra_metadata.cue_in = waveformToFloat(currentTimeRef.value);
};

const setCueOut = () => {
    form.value.extra_metadata.cue_out = waveformToFloat(currentTimeRef.value);
};

const setFadeStartNext = () => {
    form.value.extra_metadata.cross_start_next = waveformToFloat(currentTimeRef.value);
};

const setFadeIn = () => {
    const currentTime = currentTimeRef.value ?? 0;
    const cue_in = form.value.extra_metadata.cue_in ?? 0;

    form.value.extra_metadata.fade_in = waveformToFloat(currentTime - cue_in);
}

const setFadeOut = () => {
    const currentTime = currentTimeRef.value ?? 0;
    const duration = durationRef.value;
    const cue_out = form.value.extra_metadata.cue_out ?? duration ?? 0;

    form.value.extra_metadata.fade_out = waveformToFloat(cue_out - currentTime);
};
</script>
