<template>
    <div class="card">
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <div class="flex-fill text-nowrap">
                    <h5 class="card-title">
                        {{ lang_header }}
                    </h5>
                </div>
                <div class="flex-shrink-0 pl-3">
                    <volume-slider v-model.number="volume" />
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="control-group d-flex justify-content-center">
                <div class="btn-group btn-group-sm">
                    <button
                        v-if="!playing || paused"
                        class="btn btn-sm btn-success"
                        @click="play"
                    >
                        <icon icon="play_arrow" />
                    </button>
                    <button
                        v-if="playing && !paused"
                        class="btn btn-sm btn-warning"
                        @click="togglePause()"
                    >
                        <icon icon="pause" />
                    </button>
                    <button
                        class="btn btn-sm"
                        @click="previous()"
                    >
                        <icon icon="fast_rewind" />
                    </button>
                    <button
                        class="btn btn-sm"
                        @click="next()"
                    >
                        <icon icon="fast_forward" />
                    </button>
                    <button
                        class="btn btn-sm btn-danger"
                        @click="stop()"
                    >
                        <icon icon="stop" />
                    </button>
                    <button
                        class="btn btn-sm"
                        :class="{ 'btn-primary': passThrough }"
                        @click="cue()"
                    >
                        {{ $gettext('Cue') }}
                    </button>
                </div>
            </div>

            <div
                v-if="playing"
                class="mt-3"
            >
                <div class="d-flex flex-row mb-2">
                    <div class="flex-shrink-0 pt-1 pr-2">
                        {{ prettifyTime(position) }}
                    </div>
                    <div class="flex-fill">
                        <input
                            type="range"
                            min="0"
                            max="100"
                            step="0.1"
                            class="custom-range slider"
                            :value="seekingPosition"
                            @mousedown="isSeeking = true"
                            @mousemove="doSeek($event)"
                            @mouseup="isSeeking = false"
                        >
                    </div>
                    <div class="flex-shrink-0 pt-1 pl-2">
                        {{ prettifyTime(duration) }}
                    </div>
                </div>

                <div class="progress mb-1">
                    <div
                        class="progress-bar"
                        :style="{ width: volumeLeft+'%' }"
                    />
                </div>
                <div class="progress">
                    <div
                        class="progress-bar"
                        :style="{ width: volumeRight+'%' }"
                    />
                </div>
            </div>

            <div class="form-group mt-2">
                <div class="custom-file">
                    <input
                        :id="id + '_files'"
                        type="file"
                        class="custom-file-input files"
                        accept="audio/*"
                        multiple="multiple"
                        @change="addNewFiles($event.target.files)"
                    >
                    <label
                        :for="id + '_files'"
                        class="custom-file-label"
                    >
                        {{ $gettext('Add Files to Playlist') }}
                    </label>
                </div>
            </div>

            <div class="form-group mb-0">
                <div class="controls">
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input
                            :id="id + '_playthrough'"
                            v-model="playThrough"
                            type="checkbox"
                            class="custom-control-input"
                        >
                        <label
                            :for="id + '_playthrough'"
                            class="custom-control-label"
                        >
                            {{ $gettext('Continuous Play') }}
                        </label>
                    </div>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input
                            :id="id + '_loop'"
                            v-model="loop"
                            type="checkbox"
                            class="custom-control-input"
                        >
                        <label
                            :for="id + '_loop'"
                            class="custom-control-label"
                        >
                            {{ $gettext('Repeat') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="files.length > 0"
            class="list-group list-group-flush"
        >
            <a
                v-for="(rowFile, rowIndex) in files"
                href="#"
                class="list-group-item list-group-item-action flex-column align-items-start"
                :class="{ active: rowIndex === fileIndex }"
                @click.prevent="play({ fileIndex: rowIndex })"
            >
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-0">{{
                        rowFile.metadata.title ? rowFile.metadata.title : lang_unknown_title
                    }}</h5>
                    <small class="pt-1">{{ prettifyTime(rowFile.audio.length) }}</small>
                </div>
                <p class="mb-0">{{ rowFile.metadata.artist ? rowFile.metadata.artist : lang_unknown_artist }}</p>
            </a>
        </div>
    </div>
</template>

<script>
import track from './Track.js';
import _ from 'lodash';
import Icon from '~/components/Common/Icon';
import VolumeSlider from "~/components/Public/WebDJ/VolumeSlider";

export default {
    components: {VolumeSlider, Icon},
    extends: track,
    props: {
        id: String
    },
    data() {
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
    mounted () {
        this.mixGainObj = this.getStream().context.createGain();
        this.mixGainObj.connect(this.getStream().webcast);
        this.sink = this.mixGainObj;

        this.$root.$on('new-mixer-value', this.setMixGain);
        this.$root.$on('new-cue', this.onNewCue);
    },
    methods: {
        prettifyTime(time) {
            if (typeof time === 'undefined') {
                return 'N/A';
            }

            let hours = parseInt(time / 3600);
            time %= 3600;
            let minutes = parseInt(time / 60);
            let seconds = parseInt(time % 60);

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
        },
        cue() {
            this.resumeStream();
            this.$root.$emit('new-cue', (this.passThrough) ? 'off' : this.id);
        },

        onNewCue(new_cue) {
            this.passThrough = (new_cue === this.id);
        },

        setMixGain(new_value) {
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
                let ref1;
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
