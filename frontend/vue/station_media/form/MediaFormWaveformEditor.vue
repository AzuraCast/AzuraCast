<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <div class="col-md-12">
                    <h4>Visual Cue Editor <sup>BETA</sup></h4>
                    <p>You are able to set cue points and fades using the visual editor. The timestamps will be saved to the corresponding fields in the advanced playback settings.</p>
                </div>
                <b-form-group class="col-md-12">
                    <waveform ref="waveform" :audio-url="audioUrl"></waveform>
                </b-form-group>
                <b-form-group class="col-md-12">
                    <b-button-group>
                        <b-button variant="primary" @click="playAudio">
                            <i class="material-icons" aria-hidden="true">play_arrow</i>
                            <span class="sr-only">Play</span>
                        </b-button>
                        <b-button variant="danger" @click="stopAudio">
                            <i class="material-icons" aria-hidden="true">stop</i>
                            <span class="sr-only">Stop</span>
                        </b-button>
                    </b-button-group>
                    <b-button-group>
                        <b-button variant="light" @click="setCueIn">
                            <translate>Set Cue In</translate>
                        </b-button>

                        <b-button variant="light" @click="setCueOut">
                            <translate>Set Cue Out</translate>
                        </b-button>
                    </b-button-group>
                    <b-button-group>
                        <b-button variant="light" @click="setFadeOverlap">
                            <translate>Set Overlap</translate>
                        </b-button>
                    </b-button-group>
                    <b-button-group>
                        <b-button variant="light" @click="setFadeIn">
                            <translate>Set Fade In</translate>
                        </b-button>

                        <b-button variant="light" @click="setFadeOut">
                            <translate>Set Fade Out</translate>
                        </b-button>
                    </b-button-group>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
    import Waveform from "../../components/Waveform";

    export default {
        name: 'MediaFormWaveformEditor',
        components: {Waveform},
        props: {
            form: Object,
            audioUrl: String
        },
        computed: {
            langTitle() {
                return this.$gettext('Visual Cue Editor');
            }
        },
        methods: {
            playAudio() {
                this.$refs.waveform.play();
            },
            stopAudio() {
                this.$refs.waveform.stop();
            },
            setCueIn() {
                let currentTime = this.$refs.waveform.getCurrentTime();

                this.form.cue_in = Math.round((currentTime) * 10) / 10;
            },
            setCueOut() {
                let currentTime = this.$refs.waveform.getCurrentTime();

                this.form.cue_out = Math.round((currentTime) * 10) / 10;
            },
            setFadeOverlap() {
                let duration = this.$refs.waveform.getDuration();
                let currentTime = this.$refs.waveform.getCurrentTime();
                this.form.fade_overlap = Math.round((duration - currentTime) * 10) / 10;
            },
            setFadeIn() {
                let currentTime = this.$refs.waveform.getCurrentTime();
                let cue_in = this.form.cue_in || 0;

                this.form.fade_in = Math.round((currentTime - cue_in) * 10) / 10;
            },
            setFadeOut() {
                let currentTime = this.$refs.waveform.getCurrentTime();
                let cue_out = this.form.cue_out || 0;

                this.form.fade_out = Math.round((cue_out - currentTime) * 10) / 10;
            }
        }
    };
</script>