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
        @ready="onReady"
    />

    <div class="buttons mt-3">
        <div class="btn-group btn-group-sm">
            <button
                type="button"
                class="btn btn-light"
                :title="$gettext('Play')"
                @click="playAudio"
            >
                <icon :icon="IconPlayCircle" />
            </button>
            <button
                type="button"
                class="btn btn-dark"
                :title="$gettext('Stop')"
                @click="stopAudio"
            >
                <icon :icon="IconStop" />
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
import WaveformComponent from "~/components/Common/Waveform.vue";
import Icon from "~/components/Common/Icon.vue";
import {ref, shallowRef, useTemplateRef, watch} from "vue";
import {IconPlayCircle, IconStop} from "~/components/Common/icons";
import {GenericForm} from "~/entities/Forms.ts";
import {reactiveComputed} from "@vueuse/core";
import {RegionParams} from "wavesurfer.js/dist/plugins/regions.js";

const props = defineProps<{
    duration: number,
    audioUrl: string,
    waveformUrl: string,
    waveformCacheUrl?: string,
}>();

const form = defineModel<GenericForm>('form', {
    required: true
});

const $waveform = useTemplateRef('$waveform');

const durationRef = ref<number | null>(null);

const regions = shallowRef<RegionParams[]>([]);

const onReady = (duration: number) => {
    durationRef.value = duration;
};

const cueValues = reactiveComputed(() => {
    const formValue = form.value?.extra_metadata ?? {};
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
    form.value.extra_metadata.cue_in = waveformToFloat($waveform.value?.getCurrentTime());
};

const setCueOut = () => {
    form.value.extra_metadata.cue_out = waveformToFloat($waveform.value?.getCurrentTime());
};

const setFadeStartNext = () => {
    form.value.extra_metadata.cross_start_next = waveformToFloat($waveform.value?.getCurrentTime());
};

const setFadeIn = () => {
    const currentTime = $waveform.value?.getCurrentTime() ?? 0;
    const cue_in = form.value.extra_metadata.cue_in ?? 0;

    form.value.extra_metadata.fade_in = waveformToFloat(currentTime - cue_in);
}

const setFadeOut = () => {
    const currentTime = $waveform.value?.getCurrentTime() ?? 0;
    const duration = $waveform.value?.getDuration();
    const cue_out = form.value.extra_metadata.cue_out ?? duration ?? 0;

    form.value.extra_metadata.fade_out = waveformToFloat(cue_out - currentTime);
};
</script>
