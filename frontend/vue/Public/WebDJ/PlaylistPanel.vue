<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <h5 class="card-title">
                {{ lang_header }}

                <div class="float-right">
                    <input type="range" min="0" max="150" value="100" class="custom-range" v-model.number="volume">
                </div>
            </h5>
        </div>
        <div class="card-body">
            <div class="control-group d-flex justify-content-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-sm btn-success" v-if="!playing || paused" v-on:click="play">
                        <icon icon="play_arrow"></icon>
                    </button>
                    <button class="btn btn-sm btn-warning" v-if="playing && !paused" v-on:click="togglePause()">
                        <icon icon="pause"></icon>
                    </button>
                    <button class="btn btn-sm" v-on:click="previous()">
                        <icon icon="fast_rewind"></icon>
                    </button>
                    <button class="btn btn-sm" v-on:click="next()">
                        <icon icon="fast_forward"></icon>
                    </button>
                    <button class="btn btn-sm btn-danger" v-on:click="stop()">
                        <icon icon="stop"></icon>
                    </button>
                    <button class="btn btn-sm" v-on:click="cue()" v-bind:class="{ 'btn-primary': passThrough }">
                        <translate key="lang_btn_cue">Cue</translate>
                    </button>
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
                    <input v-bind:id="id + '_files'" type="file" class="custom-file-input files" accept="audio/*"
                           multiple="multiple" v-on:change="addNewFiles($event.target.files)">
                    <label v-bind:for="id + '_files'" class="custom-file-label">
                        <translate key="lang_btn_add_files_to_playlist">Add Files to Playlist</translate>
                    </label>
                </div>
            </div>

            <div class="form-group mb-0">
                <div class="custom-control custom-checkbox">
                    <input v-bind:id="id + '_playthrough'" type="checkbox" class="custom-control-input"
                           v-model="playThrough">
                    <label v-bind:for="id + '_playthrough'" class="custom-control-label">
                        <translate key="lang_continuous_play">Continuous Play</translate>
                    </label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input v-bind:id="id + '_loop'" type="checkbox" class="custom-control-input" v-model="loop">
                    <label v-bind:for="id + '_loop'" class="custom-control-label">
                        <translate key="lang_repeat_playlist">Repeat Playlist</translate>
                    </label>
                </div>
            </div>
        </div>

        <div class="list-group list-group-flush" v-if="files.length > 0">
            <a href="#" class="list-group-item list-group-item-action flex-column align-items-start"
               v-for="(rowFile, rowIndex) in files" v-bind:class="{ active: rowIndex == fileIndex }"
               v-on:click.prevent="play({ fileIndex: rowIndex })">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-0">{{
                            rowFile.metadata.title ? rowFile.metadata.title : lang_unknown_title
                        }}</h5>
                    <small class="pt-1">{{ rowFile.audio.length | prettifyTime }}</small>
                </div>
                <p class="mb-0">{{ rowFile.metadata.artist ? rowFile.metadata.artist : lang_unknown_artist }}</p>
            </a>
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
    data () {
        return {
            'fileIndex': -1,
            'files': [],

            'volume': 100,
            'duration': 0.0,
            'playThrough': true,
            'loop': false,

            'isSeeking': false,
            'seekPosition': 0,
            'mixGainObj': null
        };
    },
    computed: {
        lang_header () {
            return (this.id === 'playlist_1')
                ? this.$gettext('Playlist 1')
                : this.$gettext('Playlist 2');
        },
        lang_unknown_title () {
            return this.$gettext('Unknown Title');
        },
        lang_unknown_artist () {
            return this.$gettext('Unknown Artist');
        },
        positionPercent () {
            return (100.0 * this.position / parseFloat(this.duration));
        },
        seekingPosition () {
            return (this.isSeeking) ? this.seekPosition : this.positionPercent;
        }
    },
    props: {
        id: String
    },
    mounted () {
        this.mixGainObj = this.getStream().context.createGain();
        this.mixGainObj.connect(this.getStream().webcast);
        this.sink = this.mixGainObj;

        this.$root.$on('new-mixer-value', this.setMixGain);
        this.$root.$on('new-cue', this.onNewCue);
    },
    filters: {
        prettifyTime (time) {
            if (typeof time === 'undefined') {
                return 'N/A';
            }

            var hours = parseInt(time / 3600);
            time %= 3600;
            var minutes = parseInt(time / 60);
            var seconds = parseInt(time % 60);

            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            if (seconds < 10) {
                seconds = '0' + seconds;
            }

            if (hours > 0) {
                return hours + ':' + minutes + ':' + seconds;
            } else {
                return minutes + ':' + seconds;
            }
        }
    },
    methods: {
        cue () {
            this.resumeStream();
            this.$root.$emit('new-cue', (this.passThrough) ? 'off' : this.id);
        },

        onNewCue (new_cue) {
            this.passThrough = (new_cue === this.id);
        },

        setMixGain (new_value) {
            if (this.id === 'playlist_1') {
                this.mixGainObj.gain.value = 1.0 - new_value;
            } else {
                this.mixGainObj.gain.value = new_value;
            }
        },

        addNewFiles (newFiles) {
            _.each(newFiles, (file) => {
                file.readTaglibMetadata((data) => {
                    this.files.push({
                        file: file,
                        audio: data.audio,
                        metadata: data.metadata || { title: '', artist: '' }
                    });
                });
            });
        },

        play (options) {
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
                    title: this.file.metadata.title,
                    artist: this.file.metadata.artist
                });

                this.playing = true;
                this.paused = false;
            });
        },

        selectFile (options = {}) {
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

        previous () {
            if (!this.playing) {
                return;
            }

            return this.play({
                backward: true
            });
        },

        next () {
            if (!this.playing) {
                return;
            }

            return this.play();
        },

        onEnd () {
            this.stop();

            if (this.playThrough) {
                return this.play({
                    isAutoPlay: true
                });
            }
        },

        doSeek (e) {
            if (this.isSeeking) {
                this.seekPosition = e.target.value;
                this.seek(this.seekPosition / 100);
            }
        }
    }
};
</script>
