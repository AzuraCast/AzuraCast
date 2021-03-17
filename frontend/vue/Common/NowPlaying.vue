<template></template>
<script>
import NowPlaying from '../Entity/NowPlaying';
import axios from 'axios';
import NchanSubscriber from 'nchan';

export const nowPlayingProps = {
    props: {
        nowPlayingUri: {
            type: String,
            required: true
        },
        initialNowPlaying: {
            type: Object,
            default () {
                return NowPlaying;
            }
        },
        useNchan: {
            type: Boolean,
            default: true
        }
    }
};

export default {
    mixins: [nowPlayingProps],
    data () {
        return {
            'nchan_subscriber': null
        };
    },
    mounted () {
        // Convert initial NP data from prop to data.
        this.setNowPlaying(this.initialNowPlaying);

        setTimeout(this.checkNowPlaying, 5000);
    },
    methods: {
        checkNowPlaying () {
            if (this.useNchan) {
                this.nchan_subscriber = new NchanSubscriber(this.nowPlayingUri);
                this.nchan_subscriber.on('message', (message, message_metadata) => {
                    let np_new = JSON.parse(message);
                    setTimeout(() => {
                        this.setNowPlaying(np_new);
                    }, 5000);
                });
                this.nchan_subscriber.start();
            } else {
                axios.get(this.nowPlayingUri).then((response) => {
                    this.setNowPlaying(response.data);

                    setTimeout(this.checkNowPlaying, 15000);
                }).catch((error) => {
                    console.error(error);

                    setTimeout(this.checkNowPlaying, 30000);
                });
            }
        },
        setNowPlaying (np_new) {
            // Update the browser metadata for browsers that support it (i.e. Mobile Chrome)
            if ('mediaSession' in navigator) {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: np_new.now_playing.song.title,
                    artist: np_new.now_playing.song.artist,
                    artwork: [
                        { src: np_new.now_playing.song.art }
                    ]
                });
            }

            this.$emit('np_updated', np_new);
            this.$eventHub.$emit('np_updated', np_new);
        }
    }
};
</script>
