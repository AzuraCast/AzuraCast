<template>
    <section
        id="content"
        role="main"
        style="height: 100vh;"
    >
        <div class="container pt-5">
            <div class="form-row">
                <div class="col-md-4 mb-sm-4">
                    <settings-panel v-bind="{ stationName, baseUri, libUrls }" />
                </div>

                <div class="col-md-8">
                    <div class="form-row mb-3">
                        <div class="col-md-12">
                            <microphone-panel />
                        </div>
                    </div>
                    <div class="form-row mb-3">
                        <div class="col-md-12">
                            <mixer-panel />
                        </div>
                    </div>
                    <div class="form-row mb-4">
                        <div class="col-md-6 mb-sm-4">
                            <playlist-panel id="playlist_1" />
                        </div>

                        <div class="col-md-6">
                            <playlist-panel id="playlist_2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import MixerPanel from './WebDJ/MixerPanel.vue';
import MicrophonePanel from './WebDJ/MicrophonePanel.vue';
import PlaylistPanel from './WebDJ/PlaylistPanel.vue';
import SettingsPanel from './WebDJ/SettingsPanel.vue';

import Stream from './WebDJ/Stream.js';

export default {
    components: {
        MixerPanel,
        MicrophonePanel,
        PlaylistPanel,
        SettingsPanel
    },
    provide: function () {
        return {
            getStream: this.getStream,
            resumeStream: this.resumeStream
        };
    },
    props: {
        stationName: String,
        libUrls: Array,
        baseUri: String
    },
    data: function () {
        return {
            'stream': Stream
        };
    },
    methods: {
        getStream: function () {
            this.stream.init();

            return this.stream;
        },
        resumeStream: function () {
            this.stream.resumeContext();
        }
    }
};
</script>
