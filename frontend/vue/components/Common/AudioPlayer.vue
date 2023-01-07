<template>
    <audio
        v-if="isPlaying"
        ref="audio"
        :title="title"
    />
</template>

<script>
import getLogarithmicVolume from '~/functions/getLogarithmicVolume.js';
import Hls from 'hls.js';
import {usePlayerStore} from "~/store.js";
import {defineComponent} from "vue";

/* TODO Options API */

export default defineComponent({
    props: {
        title: {
            type: String,
            default: null
        },
        volume: {
            type: Number,
            default: 55
        },
        isMuted: {
            type: Boolean,
            default: false
        }
    },
    setup() {
        return {
            store: usePlayerStore()
        }
    },
    data() {
        return {
            'audio': null,
            'hls': null,
            'duration': 0,
            'currentTime': 0
        };
    },
    computed: {
        isPlaying() {
            return this.store.isPlaying;
        },
        current() {
            return this.store.current;
        }
    },
    watch: {
        volume(volume) {
            if (this.audio !== null) {
                this.audio.volume = getLogarithmicVolume(volume);
            }
        },
        isMuted(muted) {
            if (this.audio !== null) {
                this.audio.muted = muted;
            }
        },
        current(newCurrent) {
            let url = newCurrent.url;
            if (url === null) {
                this.stop();
            } else {
                this.play();
            }
        },
    },
    mounted() {
        // Allow pausing from the mobile metadata update.
        if ('mediaSession' in navigator) {
            navigator.mediaSession.setActionHandler('pause', () => {
                this.stop();
            });
        }
    },
    methods: {
        stop() {
            if (this.audio !== null) {
                this.audio.pause();
                this.audio.src = '';
            }

            if (this.hls !== null) {
                this.hls.destroy();
                this.hls = null;
            }

            this.duration = 0;
            this.currentTime = 0;

            this.store.stopPlaying();
        },
        play() {
            if (this.isPlaying) {
                this.stop();
                this.$nextTick(() => {
                    this.play();
                });
                return;
            }

            this.store.startPlaying();

            this.$nextTick(() => {
                this.audio = this.$refs.audio;

                // Handle audio errors.
                this.audio.onerror = (e) => {
                    if (e.target.error.code === e.target.error.MEDIA_ERR_NETWORK && this.audio.src !== '') {
                        console.log('Network interrupted stream. Automatically reconnecting shortly...');
                        setTimeout(() => {
                            this.play();
                        }, 5000);
                    }
                };

                this.audio.onended = () => {
                    this.stop();
                };

                this.audio.ontimeupdate = () => {
                    this.duration = (this.audio.duration !== Infinity && !isNaN(this.audio.duration)) ? this.audio.duration : 0;
                    this.currentTime = this.audio.currentTime;
                };

                this.audio.volume = getLogarithmicVolume(this.volume);
                this.audio.muted = this.isMuted;

                if (this.current.isHls) {
                    // HLS playback support
                    if (Hls.isSupported()) {
                        this.hls = new Hls();
                        this.hls.loadSource(this.current.url);
                        this.hls.attachMedia(this.audio);
                    } else if (this.audio.canPlayType('application/vnd.apple.mpegurl')) {
                        this.audio.src = this.current.url;
                    } else {
                        console.log('Your browser does not support HLS.');
                    }
                } else {
                    // Standard streams
                    this.audio.src = this.current.url;

                    // Firefox caches the downloaded stream, this causes playback issues.
                    // Giving the browser a new url on each start bypasses the old cache/buffer
                    if (navigator.userAgent.includes("Firefox")) {
                        this.audio.src += "?refresh=" + Date.now();
                    }
                }

                this.audio.load();
                this.audio.play();
            });
        },
        toggle(url, isStream, isHls) {
            this.store.toggle({
                url: url,
                isStream: isStream,
                isHls: isHls,
            });
        },
        getCurrentTime() {
            return this.currentTime;
        },
        getDuration() {
            return this.duration;
        },
        getProgress() {
            return (this.duration !== 0) ? +((this.currentTime / this.duration) * 100).toFixed(2) : 0;
        },
        setProgress(progress) {
            if (this.audio !== null) {
                this.audio.currentTime = (progress / 100) * this.duration;
            }
        },
    }
});
</script>
