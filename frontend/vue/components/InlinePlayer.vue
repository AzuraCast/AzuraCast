<template>
    <div style="display: contents">
        <audio-player ref="player"></audio-player>

        <div class="ml-3 player-inline" v-if="isPlaying">
            <div class="inline-seek d-inline-flex align-items-center ml-1" v-if="!current.isStream && duration !== 0">
                <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                    {{ currentTimeText }}
                </div>
                <div class="flex-fill mx-2">
                    <input type="range" :title="langSeek" class="player-seek-range custom-range" min="0" max="100"
                           step="1" v-model="progress">
                </div>
                <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                    {{ durationText }}
                </div>
            </div>

            <a class="btn btn-sm btn-outline-light px-2 ml-1" href="#" @click.prevent="stop()">
                <icon icon="stop"></icon>
                <span class="sr-only" key="lang_pause" v-translate>Stop</span>
            </a>
            <div class="inline-volume-controls d-inline-flex align-items-center ml-1">
                <div class="flex-shrink-0">
                    <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="volume = 0">
                        <icon icon="volume_mute"></icon>
                        <span class="sr-only" key="lang_mute" v-translate>Mute</span>
                    </a>
                </div>
                <div class="flex-fill mx-1">
                    <input type="range" :title="langVolume" class="player-volume-range custom-range" min="0" max="100"
                           step="1" v-model="volume">
                </div>
                <div class="flex-shrink-0">
                    <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="volume = 100">
                        <icon icon="volume_up"></icon>
                        <span class="sr-only" key="lang_full_volume" v-translate>Full Volume</span>
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
import AudioPlayer from '~/components/Common/AudioPlayer';
import formatTime from '~/functions/formatTime.js';
import Icon from '~/components/Common/Icon';
import store from '~/store.js';

export default {
    components: {Icon, AudioPlayer},
    data() {
        return {
            is_mounted: false
        };
    },
    mounted() {
        this.is_mounted = true;
    },
    computed: {
        langSeek() {
            return this.$gettext('Seek');
        },
        langVolume () {
            return this.$gettext('Volume');
        },
        durationText () {
            return formatTime(this.duration);
        },
        currentTimeText () {
            return formatTime(this.currentTime);
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
            get() {
                if (!this.is_mounted) {
                    return;
                }

                return this.$refs.player.getProgress();
            },
            set(progress) {
                this.$refs.player.setProgress(progress);
            }
        },
        isPlaying() {
            return store.state.player.isPlaying;
        },
        current() {
            return store.state.player.current;
        }
    },
    methods: {
        stop () {
            store.commit('player/toggle', {
                url: null,
                isStream: true
            });
        }
    }
};
</script>
