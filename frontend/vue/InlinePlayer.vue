<template>
    <div>
        <audio-player ref="player"></audio-player>

        <div class="ml-3 player-inline" v-if="is_playing">
            <div class="inline-seek d-inline-flex align-items-center ml-1" v-if="duration !== 0">
                <div class="flex-shrink-0 mx-1 text-muted time-display">
                    {{ currentTimeText }}
                </div>
                <div class="flex-fill mx-2">
                    <input type="range" :title="langSeek" class="player-seek-range custom-range" min="0" max="100"
                           step="1" v-model="progress">
                </div>
                <div class="flex-shrink-0 mx-1 text-muted time-display">
                    {{ durationText }}
                </div>
            </div>

            <a class="btn btn-sm btn-outline-light px-2 ml-1" href="#" @click.prevent="stop()">
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
    </div>
</template>

<style lang="scss">
    .player-inline {
        .inline-seek {
            width: 300px;

            div.time-display {
                font-size: 90%;
            }
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
    import AudioPlayer from './components/AudioPlayer';

    export default {
        components: { AudioPlayer },
        data () {
            return {
                is_mounted: false
            };
        },
        mounted () {
            this.is_mounted = true;
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
            duration () {
                if (!this.is_mounted) {
                    return;
                }

                return this.$refs.player.getDuration();
            },
            currentTime () {
                if (!this.is_mounted) {
                    return;
                }

                return this.$refs.player.getCurrentTime();
            },
            is_playing () {
                if (!this.is_mounted) {
                    return;
                }

                return this.$refs.player.isPlaying();
            },
            volume: {
                get () {
                    if (!this.is_mounted) {
                        return;
                    }

                    return this.$refs.player.getVolume();
                },
                set (vol) {
                    this.$refs.player.setVolume(vol);
                }
            },
            progress: {
                get () {
                    if (!this.is_mounted) {
                        return;
                    }

                    return this.$refs.player.getProgress();
                },
                set (progress) {
                    this.$refs.player.setProgress(progress);
                }
            }
        },
        methods: {
            play (url) {
                this.$refs.player.play(url);
            },
            stop () {
                this.$refs.player.stop();
            }
        }
    };
</script>
