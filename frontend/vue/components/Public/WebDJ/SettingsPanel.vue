<template>
    <div class="card settings">
        <div class="card-header bg-primary-dark">
            <h5 class="card-title">
                {{ $gettext('WebDJ') }}
                <br>
                <small>{{ stationName }}</small>
            </h5>
        </div>
        <div class="card-body pt-0">
            <div class="form-row pb-4">
                <div class="col-sm-12">
                    <ul class="nav nav-tabs card-header-tabs mt-0">
                        <li class="nav-item">
                            <a
                                class="nav-link active"
                                href="#settings"
                                data-toggle="tab"
                            >
                                {{ $gettext('Settings') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a
                                class="nav-link"
                                href="#metadata"
                                data-toggle="tab"
                            >
                                {{ $gettext('Metadata') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="form-row">
                <div class="col-sm-12">
                    <div class="tab-content mt-1">
                        <div
                            id="settings"
                            class="tab-pane active"
                        >
                            <div class="form-group">
                                <label class="mb-2">
                                    {{ $gettext('Encoder') }}
                                </label>
                                <div class="controls">
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input
                                            id="encoder_mp3"
                                            v-model="encoder"
                                            type="radio"
                                            value="mp3"
                                            class="custom-control-input"
                                        >
                                        <label
                                            for="encoder_mp3"
                                            class="custom-control-label"
                                        >
                                            {{ $gettext('MP3') }}
                                        </label>
                                    </div>
                                    <div class="custom-control custom-radio custom-control-inline">
                                        <input
                                            id="encoder_raw"
                                            v-model="encoder"
                                            type="radio"
                                            value="raw"
                                            class="custom-control-input"
                                        >
                                        <label
                                            for="encoder_raw"
                                            class="custom-control-label"
                                        >
                                            {{ $gettext('Raw') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label
                                    for="select_samplerate"
                                    class="mb-2"
                                >
                                    {{ $gettext('Sample Rate') }}
                                </label>
                                <div class="controls">
                                    <select
                                        id="select_samplerate"
                                        v-model.number="samplerate"
                                        class="form-control"
                                    >
                                        <option value="8000">
                                            8 kHz
                                        </option>
                                        <option value="11025">
                                            11.025 kHz
                                        </option>
                                        <option value="12000">
                                            12 kHz
                                        </option>
                                        <option value="16000">
                                            16 kHz
                                        </option>
                                        <option value="22050">
                                            22.05 kHz
                                        </option>
                                        <option value="24000">
                                            24 kHz
                                        </option>
                                        <option value="32000">
                                            32 kHz
                                        </option>
                                        <option value="44100">
                                            44.1 kHz
                                        </option>
                                        <option value="48000">
                                            48 kHz
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label
                                    for="select_bitrate"
                                    class="mb-2"
                                >
                                    {{ $gettext('Bit Rate') }}
                                </label>
                                <div class="controls">
                                    <select
                                        id="select_bitrate"
                                        v-model.number="bitrate"
                                        class="form-control"
                                    >
                                        <option value="8">
                                            8 kbps
                                        </option>
                                        <option value="16">
                                            16 kbps
                                        </option>
                                        <option value="24">
                                            24 kbps
                                        </option>
                                        <option value="32">
                                            32 kbps
                                        </option>
                                        <option value="40">
                                            40 kbps
                                        </option>
                                        <option value="48">
                                            48 kbps
                                        </option>
                                        <option value="56">
                                            56 kbps
                                        </option>
                                        <option value="64">
                                            64 kbps
                                        </option>
                                        <option value="80">
                                            80 kbps
                                        </option>
                                        <option value="96">
                                            96 kbps
                                        </option>
                                        <option value="112">
                                            112 kbps
                                        </option>
                                        <option value="128">
                                            128 kbps
                                        </option>
                                        <option value="144">
                                            144 kbps
                                        </option>
                                        <option value="160">
                                            160 kbps
                                        </option>
                                        <option value="192">
                                            192 kbps
                                        </option>
                                        <option value="224">
                                            224 kbps
                                        </option>
                                        <option value="256">
                                            256 kbps
                                        </option>
                                        <option value="320">
                                            320 kbps
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="mb-2">
                                    {{ $gettext('DJ Credentials') }}
                                </label>

                                <div class="form-row">
                                    <div class="col-6">
                                        <input
                                            v-model="djUsername"
                                            type="text"
                                            class="form-control"
                                            :placeholder="$gettext('Username')"
                                        >
                                    </div>
                                    <div class="col-6">
                                        <input
                                            v-model="djPassword"
                                            type="password"
                                            class="form-control"
                                            :placeholder="$gettext('Password')"
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <div class="custom-control custom-checkbox">
                                    <input
                                        id="use_async_worker"
                                        v-model="asynchronous"
                                        type="checkbox"
                                        class="custom-control-input"
                                    >
                                    <label
                                        for="use_async_worker"
                                        class="custom-control-label"
                                    >
                                        {{ $gettext('Use Asynchronous Worker') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div
                            id="metadata"
                            class="tab-pane"
                        >
                            <div class="form-group">
                                <label
                                    for="metadata_title"
                                    class="mb-2"
                                >
                                    {{ $gettext('Title') }}
                                </label>
                                <div class="controls">
                                    <input
                                        id="metadata_title"
                                        v-model="metadata.title"
                                        class="form-control"
                                        type="text"
                                        :disabled="!isStreaming"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <label
                                    for="metadata_artist"
                                    class="mb-2"
                                >
                                    {{ $gettext('Artist') }}
                                </label>
                                <div class="controls">
                                    <input
                                        id="metadata_artist"
                                        v-model="metadata.artist"
                                        class="form-control"
                                        type="text"
                                        :disabled="!isStreaming"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <button
                                    class="btn btn-primary"
                                    :disabled="!isStreaming"
                                    @click="updateMetadata"
                                >
                                    {{ $gettext('Update Metadata') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-actions">
            <button
                v-if="!isStreaming"
                class="btn btn-success"
                @click="startStreaming"
            >
                {{ langStreamButton }}
            </button>
            <button
                v-if="isStreaming"
                class="btn btn-danger"
                @click="stopStreaming"
            >
                {{ langStreamButton }}
            </button>
            <button
                class="btn"
                :class="{ 'btn-primary': passThrough }"
                @click="cue"
            >
                {{ $gettext('Cue') }}
            </button>
        </div>
    </div>
</template>

<script>
export default {
    inject: ['getStream', 'resumeStream'],
    props: {
        stationName: {
            type: String,
            required: true
        },
        libUrls: {
            type: Array,
            default: () => {
                return [];
            }
        },
        baseUri: {
            type: String,
            required: true
        }
    },
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
        langStreamButton () {
            return (this.isStreaming)
                ? this.$gettext('Stop Streaming')
                : this.$gettext('Start Streaming');
        },
        uri () {
            return 'wss://' + this.djUsername + ':' + this.djPassword + '@' + this.baseUri;
        }
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

            let encoderClass;
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

            let socket = this.getStream().webcast.connectSocket(encoder, this.uri);
            socket.addEventListener("open", () => {
                this.$notifySuccess(this.$gettext('Live stream connected.'));
                this.isStreaming = true;
                this.updateMetadata(false);
            });
            socket.addEventListener("close", () => {
                this.$notifyError(this.$gettext('Live stream disconnected.'));
                this.isStreaming = false;
            });
        },
        stopStreaming () {
            this.getStream().webcast.close();
            this.isStreaming = false;
        },
        updateMetadata(alert = true) {
            this.$root.$emit('metadata-update', {
                title: this.metadata.title,
                artist: this.metadata.artist
            });

            if (alert) {
                this.$notifySuccess(this.$gettext('Metadata updated!'));
            }
        },
        onMetadataUpdate (new_metadata) {
            this.metadata.title = new_metadata.title;
            this.metadata.artist = new_metadata.artist;

            return this.getStream().webcast.sendMetadata(new_metadata);
        }
    }
};
</script>
