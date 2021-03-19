<template>
    <b-form-group class="waveform-controls">
        <b-row>
            <b-form-group class="col-md-12">
                <div class="waveform__container">
                    <div id="waveform-timeline"></div>
                    <div id="waveform"></div>
                </div>
            </b-form-group>
        </b-row>
        <b-row class="mt-3">
            <b-col md="8">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <label for="waveform-zoom">
                            <translate key="lang_waveform_title">Waveform Zoom</translate>
                        </label>
                    </div>
                    <div class="flex-fill mx-3">
                        <b-form-input id="waveform-zoom" v-model="zoom" type="range" min="0" max="256" class="w-100"></b-form-input>
                    </div>
                </div>
            </b-col>
            <b-col md="4">
                <div class="inline-volume-controls d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <a class="btn btn-sm btn-outline-inverse py-0 px-3" href="#" @click.prevent="volume = 0">
                            <icon icon="volume_mute"></icon>
                            <span class="sr-only" key="lang_mute" v-translate>Mute</span>
                        </a>
                    </div>
                    <div class="flex-fill mx-1">
                        <input type="range" :title="langVolume" class="player-volume-range custom-range w-100" min="0" max="100"
                               step="1" v-model="volume">
                    </div>
                    <div class="flex-shrink-0">
                        <a class="btn btn-sm btn-outline-inverse py-0 px-3" href="#" @click.prevent="volume = 100">
                            <icon icon="volume_up"></icon>
                            <span class="sr-only" key="lang_vol_full" v-translate>Full Volume</span>
                        </a>
                    </div>
                </div>
            </b-col>
        </b-row>
    </b-form-group>
</template>

<script>
import WaveSurfer from 'wavesurfer.js';
import timeline from 'wavesurfer.js/dist/plugin/wavesurfer.timeline.js';
import regions from 'wavesurfer.js/dist/plugin/wavesurfer.regions.js';
import axios from 'axios';
import getLogarithmicVolume from '../Function/GetLogarithmicVolume.js';
import store from 'store';
import Icon from './Icon';

export default {
    name: 'Waveform',
    components: { Icon },
    props: {
        audioUrl: String,
        waveformUrl: String
    },
    data () {
        return {
            wavesurfer: null,
            zoom: 0,
            volume: 0
        };
    },
    mounted () {
        this.wavesurfer = WaveSurfer.create({
            backend: 'MediaElement',
            container: '#waveform',
            waveColor: '#2196f3',
            progressColor: '#4081CF',
            plugins: [
                timeline.create({
                    container: '#waveform-timeline',
                    primaryColor: '#222',
                    secondaryColor: '#888',
                    primaryFontColor: '#222',
                    secondaryFontColor: '#888'
                }),
                regions.create({
                    regions: []
                })
            ]
        });

        this.wavesurfer.on('ready', () => {
            this.$emit('ready');
        });

        axios.get(this.waveformUrl).then((resp) => {
            let waveform = resp.data;
            if (waveform.data) {
                this.wavesurfer.load(this.audioUrl, waveform.data);
            } else {
                this.wavesurfer.load(this.audioUrl);
            }
        }).catch((err) => {
            console.error(err);
            this.wavesurfer.load(this.audioUrl);
        });

        // Check webstorage for existing volume preference.
        if (store.enabled && store.get('player_volume') !== undefined) {
            this.volume = store.get('player_volume', 55);
        }
    },
    computed: {
        langVolume () {
            return this.$gettext('Volume');
        }
    },
    methods: {
        play () {
            if (this.wavesurfer) {
                this.wavesurfer.play();
            }
        },
        stop () {
            if (this.wavesurfer) {
                this.wavesurfer.pause();
            }
        },
        getCurrentTime () {
            if (this.wavesurfer) {
                return this.wavesurfer.getCurrentTime();
            }
        },
        getDuration () {
            if (this.wavesurfer) {
                return this.wavesurfer.getDuration();
            }
        },
        addRegion (start, end, color) {
            if (this.wavesurfer) {
                this.wavesurfer.addRegion(
                    {
                        start: start,
                        end: end,
                        resize: false,
                        drag: false,
                        color: color
                    }
                );
            }
        },
        clearRegions () {
            if (this.wavesurfer) {
                this.wavesurfer.clearRegions();
            }
        }
    },
    watch: {
        zoom: function (val) {
            this.wavesurfer.zoom(Number(val));
        },
        volume: function (volume) {
            this.wavesurfer.setVolume(getLogarithmicVolume(volume));

            if (store.enabled) {
                store.set('player_volume', volume);
            }
        }
    },
    beforeDestroy () {
        this.wavesurfer = null;
    }
};
</script>
