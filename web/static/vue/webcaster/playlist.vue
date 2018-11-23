<template>
    <div class="card">
        <h5 class="card-header">
            {{ playlist_name }}

            <div class="float-right">
                <input type="range" min="0" max="150" value="100" class="custom-range" v-model.number="volume">
            </div>
        </h5>
        <div class="card-body">
            <div class="control-group d-flex justify-content-center mb-3">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-sm btn-success play-audio player-control"><i class="material-icons">play_arrow</i></button>
                    <button class="btn btn-sm btn-warning pause-audio player-control" style="display: none;"><i class="material-icons">pause</i></button>
                    <button class="btn btn-sm previous player-control"><i class="material-icons">fast_rewind</i></button>
                    <button class="btn btn-sm next player-control"><i class="material-icons">fast_forward</i></button>
                    <button class="btn btn-sm btn-danger stop player-control"><i class="material-icons">stop</i></button>
                    <button class="btn btn-sm" v-on:click="togglePassthrough()" v-bind:class="{ 'btn-primary': pass_through }">CUE</button>
                </div>
            </div>

            <div class="progress progress-success progress-volume">
                <div class="progress-seek"></div>
                <span class="track-position-text"></span>
                <div class="bar track-position"></div>
            </div>

            <div class="progress progress-left">
                <div class="bar volume-left volume-bar" style="width: 0%"></div>
            </div>
            <div class="progress progress-right">
                <div class="bar volume-right" style="width: 0%"></div>
            </div>

            <div class="playlist form-group">
                <div class="playlist-table" style="display: none;">
                    <table class="files-table table table-striped table-bordered table-hover table-condensed">
                        <tr>
                            <th></th>
                            <th>Title</th>
                            <th>Artist</th>
                            <th>Duration</th>
                        </tr>
                    </table>
                </div>
                <div class="playlist-input custom-file">
                    <input id="{{ id }}_files" type="file" class="custom-file-input files" accept="audio/*" multiple="multiple">
                    <label for="{{ id }}_files" class="custom-file-label">Add Files to Playlist</label>
                </div>
            </div>

            <div class="form-group mb-0">
                <div class="custom-control custom-checkbox">
                    <input id="{{ id }}_playthrough" type="checkbox" class="custom-control-input" v-model="playThrough">
                    <label for="{{ id }}_playthrough" class="custom-control-label">Play Through</label>
                </div>
                <div class="custom-control custom-checkbox">
                    <input id="{{ id }}_loop" type="checkbox" class="custom-control-input" v-model="loop">
                    <label for="{{ id }}_loop" class="custom-control-label">Repeat Playlist</label>
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
            "file_index": -1,
            "files": [],
            "volume": 100,
            "playThrough": true,
            "mixGainObj": null
        };
    },
    props: {
        id: String,
        name: String
    },
    mounted: function() {
        this.mixGainObj = this.getStream().context.createGain();
        this.mixGainObj.connect(this.getStream().webcast);
        this.sink = this.mixGainObj;

        this.$root.$on('new-mixer-value', this.setMixGain);
    },
    methods: {
        cue: function() {
            this.$emit('cue', id);
        },
        setMixGain: function(new_value) {
            if (this.id === 'playlist_1') {
                this.mixGainObj.gain.value = new_value;
            } else {
                this.mixGainObj.gain.value = 1.0-new_value;
            }
        },
        appendFiles: function(newFiles, cb) {
            var addFile, files, i, j, onDone, ref1, results;
            files = this.get("files");
            onDone = _.after(newFiles.length, () => {
                this.set({
                    files: files
                });
                return typeof cb === "function" ? cb() : void 0;
            });
            addFile = function(file) {
                return file.readTaglibMetadata((data) => {
                    files.push({
                        file: file,
                        audio: data.audio,
                        metadata: data.metadata
                    });
                    return onDone();
                });
            };
            results = [];
            for (i = j = 0, ref1 = newFiles.length - 1; (0 <= ref1 ? j <= ref1 : j >= ref1); i = 0 <= ref1 ? ++j : --j) {
                results.push(addFile(newFiles[i]));
            }
            return results;
        },
        selectFile: function(options = {}) {
            if (this.files.length === 0) {
                return;
            }
            this.fileIndex += options.backward ? -1 : 1;
            if (this.fileIndex < 0) {
                this.fileIndex = this.files.length - 1;
            }
            if (this.fileIndex >= this.files.length) {
                if (!this.loop) {
                    this.fileIndex = -1;
                    return;
                }
                if (this.fileIndex < 0) {
                    this.fileIndex = this.files.length - 1;
                } else {
                    this.fileIndex = 0;
                }
            }
            this.file = this.files[this.fileIndex];
            return this.file;
        },
        play: function(file) {
            this.prepare();

            return this.getStream().createFileSource(file, this, (source) => {
                var ref1;
                this.source = source;
                this.source.connect(this.destination);
                if (this.source.duration != null) {
                    this.set({
                        duration: this.source.duration()
                    });
                } else {
                    if (((ref1 = file.audio) != null ? ref1.length : void 0) != null) {
                        this.set({
                            duration: parseFloat(file.audio.length)
                        });
                    }
                }
                this.source.play(file);
                return this.trigger("playing");
            });
        },
        onEnd: function() {
            this.stop();

            if (this.playThrough) {
                return this.play(this.selectFile());
            }
        }
    }
}
</script>
