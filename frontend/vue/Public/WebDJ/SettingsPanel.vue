<template>
    <div class="card settings">
        <div class="card-header bg-primary-dark">
            <h5 class="card-title">{{ stationName }}</h5>
        </div>
        <div class="card-body pt-0">
            <div class="row pb-4">
                <div class="col-sm-12">
                    <ul class="nav nav-tabs card-header-tabs mt-0">
                        <li class="nav-item">
                            <a class="nav-link active" href="#settings" data-toggle="tab">
                                <translate key="lang_settings_title">Settings</translate>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#metadata" data-toggle="tab">
                                <translate key="lang_metadata_title">Metadata</translate>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="tab-content mt-1">
                        <div class="tab-pane active" id="settings">
                            <div class="form-group">
                                <label class="mb-2" key="lang_encoder" v-translate>Encoder</label>
                                <div class="controls">
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input id="encoder_mp3" type="radio" v-model="encoder" value="mp3"
                                               class="custom-control-input">
                                        <label for="encoder_mp3" class="custom-control-label" key="lang_encoder_mp3" v-translate>MP3</label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input id="encoder_raw" type="radio" v-model="encoder" value="raw"
                                               class="custom-control-input">
                                        <label for="encoder_raw" class="custom-control-label" key="lang_encoder_raw" v-translate>Raw</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="select_samplerate" class="mb-2" key="lang_sample_rate" v-translate>Sample Rate</label>
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
                                <label for="select_bitrate" class="mb-2" key="lang_bit_rate" v-translate>Bit Rate</label>
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
                                <label class="mb-2" key="lang_dj_creds" v-translate>DJ Credentials</label>

                                <div class="row">
                                    <div class="col-6">
                                        <input type="text" v-model="djUsername" class="form-control"
                                               v-bind:placeholder="langDjUsername">
                                    </div>
                                    <div class="col-6">
                                        <input type="password" v-model="djPassword" class="form-control"
                                               v-bind:placeholder="langDjPassword">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <div class="custom-control custom-checkbox">
                                    <input id="use_async_worker" type="checkbox" v-model="asynchronous"
                                           class="custom-control-input">
                                    <label for="use_async_worker" class="custom-control-label">
                                        <translate key="lang_asynchronous">Use Asynchronous Worker</translate>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="metadata">
                            <div class="form-group">
                                <label for="metadata_title" class="mb-2" key="lang_metadata_title" v-translate>Title</label>
                                <div class="controls">
                                    <input id="metadata_title" class="form-control" type="text" v-model="metadata.title"
                                           v-bind:disabled="!isStreaming">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="metadata_artist" class="mb-2" key="lang_metadata_artist" v-translate>Artist</label>
                                <div class="controls">
                                    <input id="metadata_artist" class="form-control" type="text"
                                           v-model="metadata.artist" v-bind:disabled="!isStreaming">
                                </div>
                            </div>
                            <div class="form-group">
                                <button class="btn btn-primary" v-on:click="updateMetadata"
                                        v-bind:disabled="!isStreaming">
                                    <translate key="lang_btn_update_metadata">Update Metadata</translate>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-actions">
            <button class="btn btn-success" v-on:click="startStreaming" v-if="!isStreaming">
                {{ langStreamButton }}
            </button>
            <button class="btn btn-danger" v-on:click="stopStreaming" v-if="isStreaming">
                {{ langStreamButton }}
            </button>
            <button class="btn" v-on:click="cue" v-bind:class="{ 'btn-primary': passThrough }">
                <translate key="lang_btn_cue">Cue</translate>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    inject: ['getStream', 'resumeStream'],
    data () {
        return {
            'isStreaming': false,
            'djUsername': '',
            'djPassword': '',
            'bitrate': 256,
            'samplerate': 44100,
            'encoder': 'mp3',
            'asynchronous': true,
            'passThrough': false,
            'metadata': {
                'title': '',
                'artist': ''
            }
        };
    },
    computed: {
        langDjUsername () {
            return this.$gettext('Username');
        },
        langDjPassword () {
            return this.$gettext('Password');
        },
        langStreamButton () {
            return (this.isStreaming)
                ? this.$gettext('Stop Streaming')
                : this.$gettext('Start Streaming');
        },
        uri () {
            return 'wss://' + this.djUsername + ':' + this.djPassword + '@' + this.baseUri;
        }
    },
    props: {
        stationName: String,
        libUrls: Array,
        baseUri: String
    },
    mounted () {
        this.$root.$on('new-cue', this.onNewCue);
        this.$root.$on('metadata-update', this.onMetadataUpdate);
    },
    methods: {
        cue () {
            this.resumeStream();

            this.$root.$emit('new-cue', (this.passThrough) ? 'off' : 'master');
        },
        onNewCue (new_cue) {
            this.passThrough = (new_cue === 'master');
            this.getStream().webcast.setPassThrough(this.passThrough);
        },
        startStreaming () {
            this.resumeStream();

            var encoderClass;
            switch (this.encoder) {
                case 'mp3':
                    encoderClass = Webcast.Encoder.Mp3;
                    break;
                case 'raw':
                    encoderClass = Webcast.Encoder.Raw;
            }

            let encoder = new encoderClass({
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
                    scripts: this.libUrls
                });
            }

            this.getStream().webcast.connectSocket(encoder, this.uri);
            this.isStreaming = true;
        },
        stopStreaming () {
            this.getStream().webcast.close();
            this.isStreaming = false;
        },
        updateMetadata () {
            this.$root.$emit('metadata-update', {
                title: this.metadata.title,
                artist: this.metadata.artist
            });

            notify('Metadata updated!', 'success');
        },
        onMetadataUpdate (new_metadata) {
            this.metadata.title = new_metadata.title;
            this.metadata.artist = new_metadata.artist;

            return this.getStream().webcast.sendMetadata(new_metadata);
        }
    }
};
</script>
