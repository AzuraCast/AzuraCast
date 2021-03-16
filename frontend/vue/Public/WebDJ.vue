<template>
    <div class="row">
        <div class="col-md-4 mb-sm-4">
            <settings-panel v-bind="{ stationName, baseUri, libUrls }"></settings-panel>
        </div>

        <div class="col-md-8">
            <div class="row">
                <div class="col-md-8 mb-sm-4">
                    <microphone-panel></microphone-panel>
                </div>

                <div class="col-md-4 mb-sm-4">
                    <mixer-panel></mixer-panel>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 mb-sm-4">
                    <playlist-panel id="playlist_1"></playlist-panel>
                </div>

                <div class="col-md-6">
                    <playlist-panel id="playlist_2"></playlist-panel>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MixerPanel from './WebDJ/MixerPanel.vue';
import MicrophonePanel from './WebDJ/MicrophonePanel.vue';
import PlaylistPanel from './WebDJ/PlaylistPanel.vue';
import SettingsPanel from './WebDJ/SettingsPanel.vue';

import Stream from './WebDJ/Stream.js';

export default {
    data: function () {
        return {
            'stream': Stream
        };
    },
    components: {
        MixerPanel,
        MicrophonePanel,
        PlaylistPanel,
        SettingsPanel
    },
    props: {
        stationName: String,
        libUrls: Array,
        baseUri: String
    },
    provide: function () {
        return {
            getStream: this.getStream,
            resumeStream: this.resumeStream
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
