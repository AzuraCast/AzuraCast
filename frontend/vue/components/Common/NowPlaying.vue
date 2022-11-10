<template></template>
<script>
import NowPlaying from '~/components/Entity/NowPlaying';

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
        }
    }
};

export default {
    mixins: [nowPlayingProps],
    mounted () {
        // Convert initial NP data from prop to data.
        this.setNowPlaying(this.initialNowPlaying);

        setTimeout(this.checkNowPlaying, 5000);
    },
    methods: {
        checkNowPlaying () {
            this.axios.get(this.nowPlayingUri, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache',
                    'Expires': '0',
                }
            }).then((response) => {
                this.setNowPlaying(response.data);

                setTimeout(this.checkNowPlaying, (!document.hidden) ? 15000 : 30000);
            }).catch((error) => {
                setTimeout(this.checkNowPlaying, (!document.hidden) ? 30000 : 120000);
            });
        },
        setNowPlaying (np_new) {
            // Update the browser metadata for browsers that support it (i.e. Mobile Chrome)
            if ('mediaSession' in navigator) {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: np_new.now_playing.song.title,
                    artist: np_new.now_playing.song.artist,
                    artwork: [
                        {src: np_new.now_playing.song.art}
                    ]
                });
            }

            this.$emit('np_updated', np_new);
            this.$eventHub.$emit('np_updated', np_new);

            document.dispatchEvent(new CustomEvent("now-playing", {
                detail: np_new
            }));
        }
    }
};
</script>
