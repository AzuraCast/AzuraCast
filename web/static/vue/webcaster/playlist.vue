<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h5 class="card-title">
                {{ $t('headers.'+id) }}

                <div class="float-right">
                    <input type="range" min="0" max="150" value="100" class="custom-range" v-model.number="volume">
                </div>
            </h5>
        </div>
        <div class="card-body">
            <div class="control-group d-flex justify-content-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-sm btn-success" v-if="!playing || paused" v-on:click="play"><i class="material-icons">play_arrow</i></button>
                    <button class="btn btn-sm btn-warning" v-if="playing && !paused" v-on:click="togglePause()"><i class="material-icons">pause</i></button>
                    <button class="btn btn-sm" v-on:click="previous()"><i class="material-icons">fast_rewind</i></button>
                    <button class="btn btn-sm" v-on:click="next()"><i class="material-icons">fast_forward</i></button>
                    <button class="btn btn-sm btn-danger" v-on:click="stop()"><i class="material-icons">stop</i></button>
                    <button class="btn btn-sm" v-on:click="cue()" v-bind:class="{ 'btn-primary': passThrough }">{{ $t('buttons.cue') }}</button>
                </div>
            </div>

            <div class="mt-3" v-if="playing">

                <div class="d-flex flex-row mb-2">
                    <div class="flex-shrink-0 pt-1 pr-2">{{ position | prettifyTime }}</div>
                    <div class="flex-fill">
                        <input type="range" min="0" max="100" step="0.1" class="custom-range slider"
                               v-bind:value="seekingPosition"
                               v-on:mousedown="isSeeking = true"
                               v-on:mousemove="doSeek($event)"
                               v-on:mouseup="isSeeking = false">
                    </div>
                    <div class="flex-shrink-0 pt-1 pl-2">{{ duration | prettifyTime }}</div>
                </div>

                <div class="progress mb-1">
                    <div class="progress-bar" v-bind:style="{ width: volumeLeft+'%' }"></div>
                </div>
                <div class="progress">
                    <div class="progress-bar" v-bind:style="{ width: volumeRight+'%' }"></div>
                </div>
            </div>

            <div class="form-group mt-2">
                <div class="custom-file">
                    <input v-bind:id="id + '_files'" type="file" class="custom-file-input files" accept="audio/*" multiple="multiple" v-on:change="addNewFiles($event.target.files)">
                    <label v-bind:for="id + '_files'" class="custom-file-label">{{ $t('misc.addFiles') }}</label>
                </div>
            </div>

            <div class="form-group mb-0">
                <div class="custom-control custom-checkbox">
                    <input v-bind:id="id + '_playthrough'" type="checkbox" class="custom-control-input" v-model="playThrough">
                    <label v-bind:for="id + '_playthrough'" class="custom-control-label">{{ $t('settings.playThrough') }}</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input v-bind:id="id + '_loop'" type="checkbox" class="custom-control-input" v-model="loop">
                    <label v-bind:for="id + '_loop'" class="custom-control-label">{{ $t('settings.loop') }}</label>
                </div>
            </div>
        </div>

        <div class="list-group list-group-flush" v-if="files.length > 0">
            <a href="#" class="list-group-item list-group-item-action flex-column align-items-start"
               v-for="(rowFile, rowIndex) in files" v-bind:class="{ active: rowIndex == fileIndex }"
               v-on:click.prevent="play({ fileIndex: rowIndex })">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-0">{{ rowFile.metadata.title ? rowFile.metadata.title : $t('misc.unknownTitle') }}</h5>
                    <small class="pt-1">{{ rowFile.audio.length | prettifyTime }}</small>
                </div>
                <p class="mb-0">{{ rowFile.metadata.artist ? rowFile.metadata.artist : $t('misc.unknownArtist') }}</p>
            </a>
        </div>
    </div>
</template>

<script>
import track from './track.js'

export default {
    extends: track,
    data: function () {
        return {
            "fileIndex": -1,
            "files": [],

            "volume": 100,
            "duration": 0.0,
            "playThrough": true,
            "loop": false,

            "isSeeking": false,
            "seekPosition": 0,
            "mixGainObj": null
        };
    },
    computed: {
        positionPercent: function () {
            return (100.0 * this.position / parseFloat(this.duration));
        },
        seekingPosition: function () {
            return (this.isSeeking) ? this.seekPosition : this.positionPercent;
        }
    },
    props: {
        id: String
    },
    mounted: function () {
        this.mixGainObj = this.getStream().context.createGain();
        this.mixGainObj.connect(this.getStream().webcast);
        this.sink = this.mixGainObj;

        this.$root.$on('new-mixer-value', this.setMixGain);
        this.$root.$on('new-cue', this.onNewCue);
    },
    filters: {
        prettifyTime: function (time) {
            if (typeof time === 'undefined') {
                return 'N/A';
            }

            var hours = parseInt(time / 3600);
            time %= 3600;
            var minutes = parseInt(time / 60);
            var seconds = parseInt(time % 60);

            if (minutes < 10) {
                minutes = "0" + minutes;
            }
            if (seconds < 10) {
                seconds = "0" + seconds;
            }

            if (hours > 0) {
                return hours + ":" + minutes + ":" + seconds;
            } else {
                return minutes + ":" + seconds;
            }
        }
    },
    methods: {
        cue: function () {
            this.resumeStream();
            this.$root.$emit('new-cue', (this.passThrough) ? 'off' : this.id);
        },

        onNewCue: function (new_cue) {
            this.passThrough = (new_cue === this.id);
        },

        setMixGain: function (new_value) {
            if (this.id === 'playlist_1') {
                this.mixGainObj.gain.value = 1.0 - new_value;
            } else {
                this.mixGainObj.gain.value = new_value;
            }
        },

        addNewFiles: function (newFiles) {
            _.each(newFiles, (file) => {
                file.readTaglibMetadata((data) => {
                    this.files.push({
                        file: file,
                        audio: data.audio,
                        metadata: data.metadata
                    });
                });
            });
        },

        play: function (options) {
            this.resumeStream();

            if (this.paused) {
                this.togglePause();
                return;
            }

            this.stop();

            if (!(this.file = this.selectFile(options))) {
                return;
            }

            this.prepare();

            return this.getStream().createFileSource(this.file, this, (source) => {
                var ref1;
                this.source = source;
                this.source.connect(this.destination);
                if (this.source.duration != null) {
                    this.duration = this.source.duration();
                } else {
                    if (((ref1 = this.file.audio) != null ? ref1.length : void 0) != null) {
                        this.duration = parseFloat(this.file.audio.length);
                    }
                }

                this.source.play(this.file);

                this.$root.$emit('metadata-update', {
                    title: this.file.metadata.title || 'N/A',
                    artist: this.file.metadata.artist || 'N/A'
                });

                this.playing = true;
                this.paused = false;
            });
        },

        selectFile: function (options = {}) {
            if (this.files.length === 0) {
                return;
            }

            if (options.fileIndex) {
                this.fileIndex = options.fileIndex;
            } else {
                this.fileIndex += options.backward ? -1 : 1;
                if (this.fileIndex < 0) {
                    this.fileIndex = this.files.length - 1;
                }

                if (this.fileIndex >= this.files.length) {
                    if (options.isAutoPlay && !this.loop) {
                        this.fileIndex = -1;
                        return;
                    }

                    if (this.fileIndex < 0) {
                        this.fileIndex = this.files.length - 1;
                    } else {
                        this.fileIndex = 0;
                    }
                }
            }

            return this.files[this.fileIndex];
        },

        previous: function () {
            if (!this.playing) {
                return;
            }

            return this.play({
                backward: true
            });
        },

        next: function () {
            if (!this.playing) {
                return;
            }

            return this.play();
        },

        onEnd: function () {
            this.stop();

            if (this.playThrough) {
                return this.play({
                    isAutoPlay: true
                });
            }
        },

        doSeek: function (e) {
            if (this.isSeeking) {
                this.seekPosition = e.target.value;
                this.seek(this.seekPosition / 100);
            }
        }
    }
}
</script>
