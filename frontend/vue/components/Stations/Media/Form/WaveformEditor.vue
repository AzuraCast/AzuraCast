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
                @click="setFadeOverlap"
            >
                {{ $gettext('Set Overlap') }}
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
import {ref} from "vue";
import {IconPlayCircle, IconStop} from "~/components/Common/icons";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    audioUrl: {
        type: String,
        required: true
    },
    waveformUrl: {
        type: String,
        required: true
    }
});

const $waveform = ref<InstanceType<typeof WaveformComponent> | null>(null);

const playAudio = () => {
    $waveform.value?.play();
};

const stopAudio = () => {
    $waveform.value?.stop();
};

const updateRegions = () => {
    const duration = $waveform.value?.getDuration();

    const cue_in = props.form.cue_in ?? 0;
    const cue_out = props.form.cue_out ?? duration;
    const fade_overlap = props.form.fade_overlap ?? 0;
    const fade_in = props.form.fade_in ?? 0;
    const fade_out = props.form.fade_out ?? 0;

    $waveform.value?.clearRegions();

    // Create cue region
    $waveform.value?.addRegion(cue_in, cue_out, 'hsla(207,90%,54%,0.4)');

    // Create overlap region
    if (fade_overlap > cue_in) {
        $waveform.value?.addRegion(cue_out - fade_overlap, cue_out, 'hsla(29,100%,48%,0.4)');
    }

    // Create fade regions
    if (fade_in) {
        $waveform.value?.addRegion(cue_in, fade_in + cue_in, 'hsla(351,100%,48%,0.4)');
    }
    if (fade_out) {
        $waveform.value?.addRegion(cue_out - fade_out, cue_out, 'hsla(351,100%,48%,0.4)');
    }
};

const setCueIn = () => {
    const currentTime = $waveform.value?.getCurrentTime();

    props.form.cue_in = Math.round((currentTime) * 10) / 10;
    updateRegions();
};

const setCueOut = () => {
    const currentTime = $waveform.value?.getCurrentTime();

    props.form.cue_out = Math.round((currentTime) * 10) / 10;
    updateRegions();
};

const setFadeOverlap = () => {
    const duration = $waveform.value?.getDuration();
    const currentTime = $waveform.value?.getCurrentTime();
    const cue_out = props.form.cue_out ?? duration;

    props.form.fade_overlap = Math.round((cue_out - currentTime) * 10) / 10;
    updateRegions();
};

const setFadeIn = () => {
    const currentTime = $waveform.value?.getCurrentTime();
    const cue_in = props.form.cue_in ?? 0;

    props.form.fade_in = Math.round((currentTime - cue_in) * 10) / 10;
    updateRegions();
}

const setFadeOut = () => {
    const currentTime = $waveform.value?.getCurrentTime();
    const duration = $waveform.value?.getDuration();
    const cue_out = props.form.cue_out ?? duration;

    props.form.fade_out = Math.round((cue_out - currentTime) * 10) / 10;
    updateRegions();
};
</script>
