<template>
    <div class="card">
        <h5 class="card-header">
            Microphone

            <div class="float-right">
                <input type="range" min="0" max="150" value="100" class="custom-range" v-model.number="volume">
            </div>
        </h5>

        <div class="card-body">
            <div class="control-group d-flex justify-content-center mb-3">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-danger" v-on:click="toggleRecording" v-bind:class="{ active: playing }"><i class="material-icons">mic</i></button>
                    <button class="btn" v-on:click="cue" v-bind:class="{ 'btn-primary': passThrough }">CUE</button>
                </div>
            </div>

            <div class="progress progress-left">
                <div class="bar volume-left" style="width: 0%"></div>
            </div>
            <div class="progress progress-right">
                <div class="bar volume-right" style="width: 0%"></div>
            </div>

            <div class="form-group microphone-entry">
                <label for="select_microphone_source">Microphone Source</label>
                <div class="controls">
                    <select id="select_microphone_source" class="form-control"></select>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import track from './track.vue'

export default {
    extends: track,

    data: function() {
        return {
            "device": null,
            "devices": [],
            "isRecording": false
        };
    },
    watch: {
        device: function(val, oldVal) {

        }
    },
    mounted: function() {
        // Get multimedia devices by requesting them from the browser.
        navigator.mediaDevices || (navigator.mediaDevices = {});

        (base = navigator.mediaDevices).getUserMedia || (base.getUserMedia = function(constraints) {
            var fn;
            fn = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
            if (fn == null) {
                return Promise.reject(new Error("getUserMedia is not implemented in this browser"));
            }
            return new Promise(function(resolve, reject) {
                return fn.call(navigator, constraints, resolve, reject);
            });
        });

        (base1 = navigator.mediaDevices).enumerateDevices || (base1.enumerateDevices = function() {
            return Promise.reject(new Error("enumerateDevices is not implemented on this browser"));
        });


    },
    methods: {
        cue: function() {
            this.$emit('cue', (this.passThrough) ? 'off' : 'microphone');
        },
        toggleRecording: function() {
            if (this.playing) {
                this.stop();
            } else {
                this.play();
            }
        },
        createSource: function(cb) {
            var constraints;
            if (this.source != null) {
                this.source.disconnect(this.destination);
            }
            constraints = {
                video: false
            };
            if (this.get("device")) {
                constraints.audio = {
                    exact: this.get("device")
                };
            } else {
                constraints.audio = true;
            }
            return this.node.createMicrophoneSource(constraints, (source) => {
                this.source = source;
                this.source.connect(this.destination);
                return typeof cb === "function" ? cb() : void 0;
            });
        },
        play: function() {
            this.prepare();

            return this.createSource(function() {
                this.playing = true;
                this.paused = false;

                return this.trigger("playing");
            });
        }
    }
}
</script>
