<template>
    <b-tab :title="langTitle" lazy>
        <p>
            <translate key="lang_waveform_editor_desc">Set cue and fade points using the visual editor. The timestamps will be saved to the corresponding fields in the advanced playback settings.</translate>
        </p>

        <b-form-group>
            <waveform ref="waveform" :audio-url="audioUrl" :waveform-url="waveformUrl" @ready="updateRegions"></waveform>
        </b-form-group>
        <b-form-group>
            <b-button-group>
                <b-button variant="light" @click="playAudio">
                    <icon icon="play_arrow"></icon>
                    <span class="sr-only"><translate key="lang_btn_play">Play</translate></span>
                </b-button>
                <b-button variant="dark" @click="stopAudio">
                    <icon icon="stop"></icon>
                    <span class="sr-only"><translate key="lang_btn_stop">Stop</translate></span>
                </b-button>
            </b-button-group>
            <b-button-group>
                <b-button variant="primary" @click="setCueIn">
                    <translate key="lang_btn_set_cue_in">Set Cue In</translate>
                </b-button>

                <b-button variant="primary" @click="setCueOut">
                    <translate key="lang_btn_set_cue_out">Set Cue Out</translate>
                </b-button>
            </b-button-group>
            <b-button-group>
                <b-button variant="warning" @click="setFadeOverlap">
                    <translate key="lang_btn_set_fade_overlap">Set Overlap</translate>
                </b-button>
            </b-button-group>
            <b-button-group>
                <b-button variant="danger" @click="setFadeIn">
                    <translate key="lang_btn_set_fade_in">Set Fade In</translate>
                </b-button>

                <b-button variant="danger" @click="setFadeOut">
                    <translate key="lang_btn_set_fade_out">Set Fade Out</translate>
                </b-button>
            </b-button-group>
        </b-form-group>
    </b-tab>
</template>

<script>
import Waveform from '../../../Common/Waveform';
import Icon from '../../../Common/Icon';

export default {
    name: 'MediaFormWaveformEditor',
    components: { Icon, Waveform },
    props: {
        form: Object,
        audioUrl: String,
        waveformUrl: String
    },
    computed: {
        langTitle () {
            return this.$gettext('Visual Cue Editor');
        }
    },
    methods: {
        playAudio () {
            this.$refs.waveform.play();
        },
        stopAudio () {
            this.$refs.waveform.stop();
        },
        setCueIn () {
            let currentTime = this.$refs.waveform.getCurrentTime();

            this.form.cue_in = Math.round((currentTime) * 10) / 10;

            this.updateRegions();
        },
        setCueOut () {
            let currentTime = this.$refs.waveform.getCurrentTime();

            this.form.cue_out = Math.round((currentTime) * 10) / 10;

            this.updateRegions();
        },
        setFadeOverlap () {
            let duration = this.$refs.waveform.getDuration();
            let cue_out = this.form.cue_out || duration;
            let currentTime = this.$refs.waveform.getCurrentTime();

            this.form.fade_overlap = Math.round((cue_out - currentTime) * 10) / 10;

            this.updateRegions();
        },
        setFadeIn () {
            let currentTime = this.$refs.waveform.getCurrentTime();
            let cue_in = this.form.cue_in || 0;

            this.form.fade_in = Math.round((currentTime - cue_in) * 10) / 10;

            this.updateRegions();
        },
        setFadeOut () {
            let currentTime = this.$refs.waveform.getCurrentTime();
            let duration = this.$refs.waveform.getDuration();
            let cue_out = this.form.cue_out || duration;

            this.form.fade_out = Math.round((cue_out - currentTime) * 10) / 10;

            this.updateRegions();
        },
        updateRegions () {
            let duration = this.$refs.waveform.getDuration();

            let cue_in = this.form.cue_in || 0;
            let cue_out = this.form.cue_out || duration;

            let fade_overlap = this.form.fade_overlap;
            let fade_in = this.form.fade_in;
            let fade_out = this.form.fade_out;

            this.$refs.waveform.clearRegions();

            // Create cue region
            this.$refs.waveform.addRegion(cue_in, cue_out, 'hsla(207,90%,54%,0.4)');

            // Create overlap region
            if (fade_overlap > cue_in) {
                this.$refs.waveform.addRegion(cue_out - fade_overlap, cue_out, 'hsla(29,100%,48%,0.4)');
            }

            // Create fade regions
            if (fade_in) {
                this.$refs.waveform.addRegion(cue_in, fade_in + cue_in, 'hsla(351,100%,48%,0.4)');
            }
            if (fade_out) {
                this.$refs.waveform.addRegion(cue_out - fade_out, cue_out, 'hsla(351,100%,48%,0.4)');
            }
        }
    }
};
</script>
