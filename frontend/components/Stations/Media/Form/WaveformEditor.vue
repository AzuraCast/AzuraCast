<template>
    <p>
        {{
            $gettext('Set cue and fade points using the visual editor. The timestamps will be saved to the corresponding fields in the advanced playback settings.')
        }}
    </p>

    <waveform-component
        ref="$waveform"
        :audio-url="audioUrl"
        :waveform-url="waveformUrl"
        :waveform-cache-url="waveformCacheUrl"
        @ready="updateRegions"
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
import WaveformComponent from '~/components/Common/Waveform.vue';
import Icon from '~/components/Common/Icon.vue';
import {useTemplateRef} from "vue";
import {IconPlayCircle, IconStop} from "~/components/Common/icons";
import {GenericForm} from "~/entities/Forms.ts";
import {useVModel} from "@vueuse/core";

const props = withDefaults(
    defineProps<{
        form: GenericForm,
        audioUrl: string,
        waveformUrl: string,
        waveformCacheUrl?: string,
    }>(),
    {
        waveformCacheUrl: null
    }
);

const emit = defineEmits<{
    (e: 'update:form', form: GenericForm): void
}>();

const form = useVModel(props, 'form', emit);

const $waveform = useTemplateRef('$waveform');

const playAudio = () => {
    $waveform.value?.play();
};

const stopAudio = () => {
    $waveform.value?.stop();
};

const updateRegions = () => {
    const duration = $waveform.value?.getDuration();

    const {
        cue_in = 0,
        cue_out = duration,
        cross_start_next: fade_start_next = 0,
        fade_in = 0,
        fade_out = 0,
    } = form.value;

    $waveform.value?.clearRegions();

    // Create cue region
    $waveform.value?.addRegion(cue_in, cue_out, 'hsla(207,90%,54%,0.4)');

    // Create fade start next region
    if (fade_start_next > cue_in) {
        $waveform.value?.addRegion(fade_start_next, cue_out, 'hsla(29,100%,48%,0.4)');
    }

    // Create fade regions
    if (fade_in) {
        $waveform.value?.addRegion(cue_in, fade_in + cue_in, 'hsla(351,100%,48%,0.4)');
    }
    if (fade_out) {
        $waveform.value?.addRegion(cue_out - fade_out, cue_out, 'hsla(351,100%,48%,0.4)');
    }
};

const waveformToFloat = (value) => Math.round((value) * 10) / 10;

const setCueIn = () => {
    form.value.extra_metadata.cue_in = waveformToFloat($waveform.value?.getCurrentTime());
    updateRegions();
};

const setCueOut = () => {
    form.value.extra_metadata.cue_out = waveformToFloat($waveform.value?.getCurrentTime());
    updateRegions();
};

const setFadeStartNext = () => {
    form.value.extra_metadata.cross_start_next = waveformToFloat($waveform.value?.getCurrentTime());
    updateRegions();
};

const setFadeIn = () => {
    const currentTime = $waveform.value?.getCurrentTime();
    const cue_in = form.value.extra_metadata.cue_in ?? 0;

    form.value.extra_metadata.fade_in = waveformToFloat(currentTime - cue_in);
    updateRegions();
}

const setFadeOut = () => {
    const currentTime = $waveform.value?.getCurrentTime();
    const duration = $waveform.value?.getDuration();
    const cue_out = form.value.extra_metadata.cue_out ?? duration;

    form.value.extra_metadata.fade_out = waveformToFloat(cue_out - currentTime);
    updateRegions();
};
</script>
