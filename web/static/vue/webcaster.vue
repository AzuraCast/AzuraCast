<template>
    <div class="row">
        <div class="col-md-4 mb-sm-4">
            <settings v-bind:station-name="stationName" v-bind:base-uri="baseUri"></settings>
        </div>

        <div class="col-md-8">
            <div class="row">
                <div class="col-md-8 mb-sm-4">
                    <microphone></microphone>
                </div>

                <div class="col-md-4 mb-sm-4">
                    <mixer></mixer>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6 mb-sm-4">
                    <playlist name="Playlist 1" id="playlist_1"></playlist>
                </div>

                <div class="col-md-6">
                    <playlist name="Playlist 2" id="playlist_2"></playlist>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import mixer from './webcaster/mixer.vue'
import microphone from './webcaster/microphone.vue'
import playlist from './webcaster/playlist.vue'
import settings from './webcaster/settings.vue'

import stream from './webcaster/stream.js';

export default {
    data: function() {
        return {
            "stream": stream
        };
    },
    components: {
        mixer,
        microphone,
        playlist,
        settings
    },
    props: {
        stationName: String,
        baseUri: String
    },
    provide: function() {
        return {
            getStream: this.getStream
        };
    },
    methods: {
        getStream: function() {
            return this.stream;
        }
    }
}
</script>
