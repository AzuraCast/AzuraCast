<template>
    <div class="card settings">
        <div class="card-header">
            <h5>{{ stationName }}</h5>

            <ul class="nav nav-tabs card-header-tabs mt-0">
                <li class="nav-item ml-1">
                    <a class="nav-link active" href="#settings" data-toggle="tab">Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#metadata" data-toggle="tab">Metadata</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="settings">
                    <div class="form-group">
                        <label>Encoder</label>
                        <div class="controls">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input id="encoder_mp3" type="radio" v-model="encoder" value="mp3" class="custom-control-input">
                                <label for="encoder_mp3" class="custom-control-label">MP3</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input id="encoder_raw" type="radio" v-model="encoder" value="raw" class="custom-control-input">
                                <label for="encoder_raw" class="custom-control-label">Raw</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="select_samplerate">Sample Rate</label>
                        <div class="controls">
                            <select id="select_samplerate" class="form-control" v-model.number="samplerate">
                                <option value="8000">8 kHz</option>
                                <option value="11025">11.025 kHz</option>
                                <option value="12000">12 kHz</option>
                                <option value="16000">16 kHz</option>
                                <option value="22050">22.05 kHz</option>
                                <option value="24000">24 kHz</option>
                                <option value="32000">32 kHz</option>
                                <option value="44100">44.1 kHz</option>
                                <option value="48000">48 kHz</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="select_bitrate">Bit Rate</label>
                        <div class="controls">
                            <select id="select_bitrate" class="form-control" v-model.number="bitrate">
                                <option value="8">8 kbps</option>
                                <option value="16">16 kbps</option>
                                <option value="24">24 kbps</option>
                                <option value="32">32 kbps</option>
                                <option value="40">40 kbps</option>
                                <option value="48">48 kbps</option>
                                <option value="56">56 kbps</option>
                                <option value="64">64 kbps</option>
                                <option value="80">80 kbps</option>
                                <option value="96">96 kbps</option>
                                <option value="112">112 kbps</option>
                                <option value="128">128 kbps</option>
                                <option value="144">144 kbps</option>
                                <option value="160">160 kbps</option>
                                <option value="192">192 kbps</option>
                                <option value="224">224 kbps</option>
                                <option value="256">256 kbps</option>
                                <option value="320">320 kbps</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>DJ Credentials</label>

                        <div class="row">
                            <div class="col-6">
                                <input type="text" v-model="djUsername" class="form-control" placeholder="Username">
                            </div>
                            <div class="col-6">
                                <input type="text" v-model="djPassword" class="form-control" placeholder="Password">
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <div class="custom-control custom-checkbox">
                            <input id="use_async_worker" type="checkbox" v-model="asynchronous" class="custom-control-input">
                            <label for="use_async_worker" class="custom-control-label">Use Asynchronous Worker</label>
                        </div>
                    </div>
                </div>
                <div class="tab-pane" id="metadata">
                    <div class="alert metadata-updated" style="display: none;">
                        Metadata updated!
                    </div>
                    <div class="form-group">
                        <label for="metadata_title">Title</label>
                        <div class="controls">
                            <input id="metadata_title" class="form-control" type="text" v-model="metadata.title" v-bind:disabled="!isStreaming">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="metadata_artist">Artist</label>
                        <div class="controls">
                            <input id="metadata_artist" class="form-control" type="text" v-model="metadata.artist" v-bind:disabled="!isStreaming">
                        </div>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary" v-on:click="updateMetadata" v-bind:disabled="!isStreaming">Update Metadata</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-actions">
            <button class="btn btn-success" v-on:click="startStreaming" v-if="!isStreaming">Start streaming</button>
            <button class="btn btn-danger" v-on:click="stopStreaming" v-if="isStreaming">Stop streaming</button>
            <button class="btn" v-on:click="cue" v-bind:class="{ 'btn-primary': passThrough }">Cue</button>
        </div>
    </div>
</template>

<script>
export default {
    inject: ['getStream'],
    data: function() {
        return {
            "isStreaming": false,
            "djUsername": "",
            "djPassword": "",
            "bitrate": 128,
            "samplerate": 44100,
            "encoder": "mp3",
            "asynchronous": true,
            "passThrough": false,
            "metadata": {
                "title": "",
                "artist": "",
            }
        };
    },
    computed: {
        uri: function() {
            return 'wss://'+this.djUsername+':'+this.djPassword+'@'+this.baseUri;
        }
    },
    props: {
        stationName: String,
        baseUri: String
    },
    mounted: function() {
        this.$root.$on('new-cue', this.onNewCue);
    },
    methods: {
        cue: function() {
            this.$root.$emit('new-cue', (this.passThrough) ? 'off' : 'master');
        },
        onNewCue: function(new_cue) {
            this.passThrough = (new_cue === 'master');
        },
        startStreaming: function() {
            var encoderClass;
            switch (this.encoder) {
                case "mp3":
                    encoderClass = Webcast.Encoder.Mp3;
                    break;
                case "raw":
                    encoderClass = Webcast.Encoder.Raw;
            }

            var encoder = new encoderClass({
                channels: 2,
                samplerate: this.samplerate,
                bitrate: this.bitrate
            });

            if (this.samplerate !== this.getStream().context.sampleRate) {
                encoder = new Webcast.Encoder.Resample({
                    encoder: encoder,
                    type: Samplerate.LINEAR,
                    samplerate: this.getStream().context.sampleRate
                });
            }

            if (this.asynchronous) {
                encoder = new Webcast.Encoder.Asynchronous({
                    encoder: encoder,
                    scripts: [
                        "https://cdn.rawgit.com/webcast/libsamplerate.js/master/dist/libsamplerate.js",
                        "https://cdn.rawgit.com/savonet/shine/master/js/dist/libshine.js",
                        "https://cdn.rawgit.com/webcast/webcast.js/master/lib/webcast.js"
                    ]
                });
            }

            this.getStream().webcast.connectSocket(encoder, this.uri);
            this.isStreaming = true;
        },
        stopStreaming: function() {
            this.getStream().webcast.close();
            this.isStreaming = false;
        },
        updateMetadata: function() {
            return this.getStream().webcast.sendMetadata({
                "title": this.metadata.title,
                "artist": this.metadata.artist
            });
        },
    }
}
</script>
