<template>
    <b-form-group>
        <b-row>
            <b-form-group class="col-md-12">
                <div class="waveform__container">
                    <div id="waveform-timeline"></div>
                    <div id="waveform"></div>
                </div>
            </b-form-group>
            <b-form-group class="col-md-12" label-for="waveform-zoom">
                <template v-slot:label>
                    <translate>Waveform Zoom</translate>
                </template>

                <b-form-input id="waveform-zoom" v-model="zoom" type="range" min="0" max="256"></b-form-input>
            </b-form-group>
        </b-row>
    </b-form-group>
</template>

<script>
    import WaveSurfer from 'wavesurfer.js'
    import timeline from 'wavesurfer.js/dist/plugin/wavesurfer.timeline.js'

    export default {
        name: 'Waveform',
        props: {
            audioUrl: String
        },
        data() {
            return {
                wavesurfer: null,
                zoom: 0
            };
        },
        mounted() {
            this.wavesurfer = WaveSurfer.create({
                backend: 'MediaElement',
                container: '#waveform',
                waveColor: '#2196f3',
                progressColor: '#4081CF',
                plugins: [
                    timeline.create({
                        container: '#waveform-timeline'
                    })
                ]
            });

            this.wavesurfer.load(this.audioUrl);
        },
        methods: {
            play() {
                if (this.wavesurfer) {
                    this.wavesurfer.play();
                }
            },
            stop() {
                if (this.wavesurfer) {
                    this.wavesurfer.pause();
                }
            },
            getCurrentTime() {
                if (this.wavesurfer) {
                    return this.wavesurfer.getCurrentTime();
                }
            },
            getDuration() {
                if (this.wavesurfer) {
                    return this.wavesurfer.getDuration();
                }
            }
        },
        watch: {
            zoom: function (val) {
                this.wavesurfer.zoom(Number(val));
            }
        },
        beforeDestroy() {
            this.wavesurfer = null;
        }
    };
</script>

<style lang="scss">
    .waveform__container {
        border: 1px solid #757575;
        border-radius: 4px;
    }
</style>