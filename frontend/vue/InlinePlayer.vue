<template>
    <div class="ml-3 player-inline" v-if="isPlaying">
        <audio ref="player"/>

        <div class="inline-seek d-inline-flex align-items-center ml-1" v-if="duration !== 0">
            <div class="flex-shrink-0 mx-2 text-muted time-display">
                {{ currentTimeText }}
            </div>
            <div class="flex-fill mx-2">
                <input type="range" :title="langSeek" class="player-seek-range custom-range" min="0" max="100"
                       step="1" v-model="progress">
            </div>
            <div class="flex-shrink-0 mx-2 text-muted time-display">
                {{ durationText }}
            </div>
        </div>

        <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="stop()">
            <i class="material-icons" aria-hidden="true">pause</i>
            <span class="sr-only" v-translate>Pause</span>
        </a>
        <div class="inline-volume-controls d-inline-flex align-items-center ml-1">
            <div class="flex-shrink-0">
                <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="volume = 0">
                    <i class="material-icons" aria-hidden="true">volume_mute</i>
                    <span class="sr-only" v-translate>Mute</span>
                </a>
            </div>
            <div class="flex-fill mx-1">
                <input type="range" :title="langVolume" class="player-volume-range custom-range" min="0" max="100"
                       step="1" v-model="volume">
            </div>
            <div class="flex-shrink-0">
                <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="volume = 100">
                    <i class="material-icons" aria-hidden="true">volume_up</i>
                    <span class="sr-only" v-translate>Full Volume</span>
                </a>
            </div>
        </div>
    </div>
</template>

<style lang="scss">
    .player-inline {
        .inline-seek {
            width: 300px;
        }

        .inline-volume-controls {
            width: 175px;
        }

        input.player-volume-range,
        input.player-seek-range {
            width: 100%;
            height: 10px;
        }
    }
</style>

<script>
    import store from 'store';
    import getLogarithmicVolume from './inc/logarithmic_volume';

    export default {
        data () {
            return {
                'isPlaying': false,
                'volume': 55,
                'audio': null,
                'duration': 0,
                'currentTime': 0
            };
        },
        created () {
            // Allow pausing from the mobile metadata update.
            if ('mediaSession' in navigator) {
                navigator.mediaSession.setActionHandler('pause', () => {
                    this.stop();
                });
            }

            // Check webstorage for existing volume preference.
            if (store.enabled && store.get('player_volume') !== undefined) {
                this.volume = store.get('player_volume', this.volume);
            }

            this.$eventHub.$on('player_toggle', (url) => {
                if (this.isPlaying && this.audio.src === url) {
                    this.stop();
                } else {
                    this.stop();
                    Vue.nextTick(() => {
                        this.play(url);
                    });
                }
            });
        },
        computed: {
            langSeek () {
                return this.$gettext('Seek');
            },
            langVolume () {
                return this.$gettext('Volume');
            },
            durationText () {
                let minutes = Math.floor(this.duration / 60),
                        seconds_int = this.duration - minutes * 60,
                        seconds_str = seconds_int.toString(),
                        seconds = seconds_str.substr(0, 2);

                return minutes + ':' + seconds;
            },
            currentTimeText () {
                let current_minute = parseInt(this.currentTime / 60) % 60,
                        current_seconds_long = this.currentTime % 60,
                        current_seconds = current_seconds_long.toFixed();

                return (current_minute < 10 ? '0' + current_minute : current_minute) + ':' + (current_seconds < 10 ? '0' + current_seconds : current_seconds);
            },
            progress: {
                get () {
                    return (this.duration !== 0) ? Math.round((this.currentTime / this.duration) * 100, 2) : 0;
                },
                set (progress) {
                    if (this.audio !== null) {
                        this.audio.currentTime = (progress / 100) * this.duration;
                    }
                }
            }
        },
        watch: {
            volume (volume) {
                if (this.audio !== null) {
                    this.audio.volume = getLogarithmicVolume(volume);
                }

                if (store.enabled) {
                    store.set('player_volume', volume);
                }
            }
        },
        methods: {
            play (url) {
                if (this.isPlaying) {
                    this.stop();
                    Vue.nextTick(() => {
                        this.play(url);
                    });
                }

                this.isPlaying = true;

                Vue.nextTick(() => {
                    this.audio = this.$refs.player;

                    this.audio.onended = () => {
                        this.stop();
                    };

                    this.audio.ontimeupdate = () => {
                        this.duration = (this.audio.duration !== Infinity && !isNaN(this.audio.duration)) ? this.audio.duration : 0;
                        this.currentTime = this.audio.currentTime;
                    };

                    this.audio.volume = getLogarithmicVolume(this.volume);

                    this.audio.src = url;

                    this.audio.load();
                    this.audio.play();
                });

                this.$eventHub.$emit('player_playing', url);
            },
            stop () {
                if (!this.isPlaying) {
                    return;
                }

                this.$eventHub.$emit('player_stopped', this.audio.src);

                this.audio.pause();
                this.audio.src = '';

                this.duration = 0;
                this.currentTime = 0;
                this.isPlaying = false;
            }
        }
    };
</script>
