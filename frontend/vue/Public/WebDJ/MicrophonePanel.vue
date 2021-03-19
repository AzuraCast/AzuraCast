<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h5 class="card-title">
                <translate key="lang_mic_title">Microphone</translate>

                <div class="float-right">
                    <input type="range" min="0" max="150" value="100" class="custom-range" v-model.number="volume">
                </div>
            </h5>
        </div>

        <div class="card-body">
            <div class="control-group d-flex justify-content-center mb-3">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-danger" v-on:click="toggleRecording" v-bind:class="{ active: playing }">
                        <icon icon="mic"></icon>
                    </button>
                    <button class="btn" v-on:click="cue" v-bind:class="{ 'btn-primary': passThrough }">
                        <translate key="lang_btn_cue">Cue</translate>
                    </button>
                </div>
            </div>

            <div v-if="playing">
                <div class="progress mb-1">
                    <div class="progress-bar" v-bind:style="{ width: volumeLeft+'%' }"></div>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar" v-bind:style="{ width: volumeRight+'%' }"></div>
                </div>
            </div>

            <div class="form-group microphone-entry">
                <label for="select_microphone_source" class="mb-2" key="lang_mic_source" v-translate>Microphone Source</label>
                <div class="controls">
                    <select id="select_microphone_source" v-model="device" class="form-control">
                        <option v-for="device_row in devices" v-bind:value="device_row.deviceId">
                            {{ device_row.label }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import track from './Track.js';
import _ from 'lodash';
import Icon from '../../Common/Icon';

export default {
    components: { Icon },
    extends: track,

    data: function () {
        return {
            'device': null,
            'devices': [],
            'isRecording': false
        };
    },
    watch: {
        device: function (val, oldVal) {
            if (this.source == null) {
                return;
            }
            return this.createSource();
        }
    },
    mounted: function () {
        var base, base1;

        // Get multimedia devices by requesting them from the browser.
        navigator.mediaDevices || (navigator.mediaDevices = {});

        (base = navigator.mediaDevices).getUserMedia || (base.getUserMedia = function (constraints) {
            var fn;
            fn = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
            if (fn == null) {
                return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
            }
            return new Promise(function (resolve, reject) {
                return fn.call(navigator, constraints, resolve, reject);
            });
        });

        (base1 = navigator.mediaDevices).enumerateDevices || (base1.enumerateDevices = function () {
            return Promise.reject(new Error('enumerateDevices is not implemented on this browser'));
        });

        var vm_mic = this;
        navigator.mediaDevices.getUserMedia({
            audio: true,
            video: false
        }).then(function () {
            return navigator.mediaDevices.enumerateDevices().then(vm_mic.setDevices);
        });

        this.$root.$on('new-cue', this.onNewCue);
    },
    methods: {
        cue: function () {
            this.resumeStream();
            this.$root.$emit('new-cue', (this.passThrough) ? 'off' : 'microphone');
        },
        onNewCue: function (new_cue) {
            this.passThrough = (new_cue === 'microphone');
        },
        toggleRecording: function () {
            this.resumeStream();

            if (this.playing) {
                this.stop();
            } else {
                this.play();
            }
        },
        createSource: function (cb) {
            var constraints;
            if (this.source != null) {
                this.source.disconnect(this.destination);
            }
            constraints = {
                video: false
            };
            if (this.device) {
                constraints.audio = {
                    deviceId: this.device
                };
            } else {
                constraints.audio = true;
            }
            return this.getStream().createMicrophoneSource(constraints, (source) => {
                this.source = source;
                this.source.connect(this.destination);
                return typeof cb === 'function' ? cb() : void 0;
            });
        },
        play: function () {
            this.prepare();

            return this.createSource(() => {
                this.playing = true;
                this.paused = false;
            });
        },
        setDevices: function (devices) {
            devices = _.filter(devices, function ({ kind, deviceId }) {
                return kind === 'audioinput';
            });
            if (_.isEmpty(devices)) {
                return;
            }

            this.devices = devices;
            this.device = _.first(devices).deviceId;
        }
    }
};
</script>
