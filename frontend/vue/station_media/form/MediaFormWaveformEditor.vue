<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <div class="col-md-12">
                    <h4>Visual Cue Editor <sup>BETA</sup></h4>
                    <p>You are able to set cue points and fades using the visual editor. The timestamps will be saved to
                        the corresponding fields in the advanced playback settings.</p>
                </div>
                <b-form-group class="col-md-12">
                    <waveform ref="waveform" :audio-url="audioUrl"></waveform>
                </b-form-group>
                <b-form-group class="col-md-12">
                    <b-button-group>
                        <b-button variant="light" @click="playAudio">
                            <i class="material-icons" aria-hidden="true">play_arrow</i>
                            <span class="sr-only">Play</span>
                        </b-button>
                        <b-button variant="dark" @click="stopAudio">
                            <i class="material-icons" aria-hidden="true">stop</i>
                            <span class="sr-only">Stop</span>
                        </b-button>
                    </b-button-group>
                    <b-button-group>
                        <b-button variant="primary" @click="setCueIn">
                            <translate>Set Cue In</translate>
                        </b-button>

                        <b-button variant="primary" @click="setCueOut">
                            <translate>Set Cue Out</translate>
                        </b-button>
                    </b-button-group>
                    <b-button-group>
                        <b-button variant="warning" @click="setFadeOverlap">
                            <translate>Set Overlap</translate>
                        </b-button>
                    </b-button-group>
                    <b-button-group>
                        <b-button variant="danger" @click="setFadeIn">
                            <translate>Set Fade In</translate>
                        </b-button>

                        <b-button variant="danger" @click="setFadeOut">
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

                this.updateRegions();
            },
            setCueOut() {
                let currentTime = this.$refs.waveform.getCurrentTime();

                this.form.cue_out = Math.round((currentTime) * 10) / 10;

                this.updateRegions();
            },
            setFadeOverlap() {
                let duration = this.$refs.waveform.getDuration();
                let cue_out = this.form.cue_out || duration;
                let currentTime = this.$refs.waveform.getCurrentTime();

                this.form.fade_overlap = Math.round((cue_out - currentTime) * 10) / 10;

                this.updateRegions();
            },
            setFadeIn() {
                let currentTime = this.$refs.waveform.getCurrentTime();
                let cue_in = this.form.cue_in || 0;

                this.form.fade_in = Math.round((currentTime - cue_in) * 10) / 10;

                this.updateRegions();
            },
            setFadeOut() {
                let currentTime = this.$refs.waveform.getCurrentTime();
                let duration = this.$refs.waveform.getDuration();
                let cue_out = this.form.cue_out || duration;

                this.form.fade_out = Math.round((cue_out - currentTime) * 10) / 10;

                this.updateRegions();
            },
            updateRegions() {
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
        },
        mounted() {
            this.$refs.waveform.wavesurfer.on('ready', this.updateRegions)
        }
    };
</script>