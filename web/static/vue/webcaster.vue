<template>
    <div class="row">
        <div class="col-md-4 mb-sm-4">
            <settings v-bind:station-name="stationName" v-bind:base-uri="baseUri" v-on:cue="handleCue"></settings>
        </div>

        <div class="col-md-8">
            <div class="row mb-4">
                <div class="col-md-6 mb-sm-4">
                    <playlist name="Playlist 1" id="playlist_1" v-bind.sync="playlist_1" v-on:cue="handleCue"></playlist>
                </div>

                <div class="col-md-6">
                    <playlist name="Playlist 2" id="playlist_2" v-bind.sync="playlist_2" v-on:cue="handleCue"></playlist>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-sm-4">
                    <mixer v-bind.sync="mixer"></mixer>
                </div>

                <div class="col-md-8">
                    <microphone v-bind.sync="microphone" v-on:cue="handleCue"></microphone>
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


module.exports = {
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
