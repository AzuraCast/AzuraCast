<template>
    <p>
        {{
            $gettext('Set cue and fade points using the visual editor. The timestamps will be saved to the corresponding fields in the advanced playback settings.')
        }}
    </p>

    <b-form-group>
        <waveform-component
            ref="$waveform"
            :audio-url="audioUrl"
            :waveform-url="waveformUrl"
            @ready="updateRegions"
        />
    </b-form-group>
    <b-form-group>
        <div class="buttons">
            <b-button-group size="sm">
                <b-button
                    variant="light"
                    :title="$gettext('Play')"
                    size="sm"
                    @click="playAudio"
                >
                    <icon icon="play_arrow" />
                </b-button>
                <b-button
                    variant="dark"
                    :title="$gettext('Stop')"
                    size="sm"
                    @click="stopAudio"
                >
                    <icon icon="stop" />
                </b-button>
            </b-button-group>
            <b-button-group size="sm">
                <b-button
                    variant="primary"
                    size="sm"
                    @click="setCueIn"
                >
                    {{ $gettext('Set Cue In') }}
                </b-button>
                <b-button
                    variant="primary"
                    size="sm"
                    @click="setCueOut"
                >
                    {{ $gettext('Set Cue Out') }}
                </b-button>
            </b-button-group>
            <b-button-group size="sm">
                <b-button
                    variant="warning"
                    size="sm"
                    @click="setFadeOverlap"
                >
                    {{ $gettext('Set Overlap') }}
                </b-button>
            </b-button-group>
            <b-button-group size="sm">
                <b-button
                    variant="danger"
                    size="sm"
                    @click="setFadeIn"
                >
                    {{ $gettext('Set Fade In') }}
                </b-button>

                <b-button
                    variant="danger"
                    size="sm"
                    @click="setFadeOut"
                >
                    {{ $gettext('Set Fade Out') }}
                </b-button>
            </b-button-group>
        </div>
    </b-form-group>
</template>

<script setup>
import WaveformComponent from '~/components/Common/Waveform';
import Icon from '~/components/Common/Icon';
import {ref} from "vue";

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

const $waveform = ref(); // Waveform

const playAudio = () => {
    $waveform.value?.play();
};

const stopAudio = () => {
    $waveform.value?.stop();
};

const updateRegions = () => {
    let duration = $waveform.value?.getDuration();

    let cue_in = props.form.cue_in ?? 0;
    let cue_out = props.form.cue_out ?? duration;
    let fade_overlap = props.form.fade_overlap ?? 0;
    let fade_in = props.form.fade_in ?? 0;
    let fade_out = props.form.fade_out ?? 0;

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
    let currentTime = $waveform.value?.getCurrentTime();

    props.form.cue_in = Math.round((currentTime) * 10) / 10;
    updateRegions();
};

const setCueOut = () => {
    let currentTime = $waveform.value?.getCurrentTime();

    props.form.cue_out = Math.round((currentTime) * 10) / 10;
    updateRegions();
};

const setFadeOverlap = () => {
    let duration = $waveform.value?.getDuration();
    let currentTime = $waveform.value?.getCurrentTime();
    let cue_out = form.value?.cue_out ?? duration;

    props.form.fade_overlap = Math.round((cue_out - currentTime) * 10) / 10;
    updateRegions();
};

const setFadeIn = () => {
    let currentTime = $waveform.value?.getCurrentTime();
    let cue_in = form.value?.cue_in ?? 0;

    props.form.fade_in = Math.round((currentTime - cue_in) * 10) / 10;
    updateRegions();
}

const setFadeOut = () => {
    let currentTime = $waveform.value?.getCurrentTime();
    let duration = $waveform.value?.getDuration();
    let cue_out = form.value?.cue_out ?? duration;

    props.form.fade_out = Math.round((cue_out - currentTime) * 10) / 10;
    updateRegions();
};
</script>
