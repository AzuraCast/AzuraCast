<template>
    <div class="ml-3 player-inline" v-if="isPlaying">
        <audio ref="player"/>

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
                <input type="range" :title="lang_volume" class="player-volume-range custom-range" min="0" max="100"
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
        .inline-volume-controls {
            width: 175px;
        }

        input.player-volume-range {
            width: 100%;
            height: 10px;
        }
    }
</style>

<script>
    import store from 'store'
    import getLogarithmicVolume from './inc/logarithmic_volume'

    export default {
        data () {
            return {
                'isPlaying': false,
                'volume': 55,
                'audio': null
            }
        },
        created () {
            // Allow pausing from the mobile metadata update.
            if ('mediaSession' in navigator) {
                navigator.mediaSession.setActionHandler('pause', () => {
                    this.stop()
                })
            }

            // Check webstorage for existing volume preference.
            if (store.enabled && store.get('player_volume') !== undefined) {
                this.volume = store.get('player_volume', this.volume)
            }

            this.$eventHub.$on('player_toggle', (url) => {
                if (this.isPlaying && this.audio.src === url) {
                    this.stop()
                } else {
                    this.stop()
                    Vue.nextTick(() => {
                        this.play(url)
                    })
                }
            })
        },
        computed: {
            lang_volume () {
                return this.$gettext('Volume')
            }
        },
        watch: {
            volume (volume) {
                if (this.audio !== null) {
                    this.audio.volume = getLogarithmicVolume(volume)
                }

                if (store.enabled) {
                    store.set('player_volume', volume)
                }
            }
        },
        methods: {
            play (url) {
                if (this.isPlaying) {
                    this.stop()
                    Vue.nextTick(() => {
                        this.play(url)
                    })
                }

                this.isPlaying = true

                Vue.nextTick(() => {
                    this.audio = this.$refs.player

                    this.audio.onended = () => {
                        this.stop()
                    }

                    this.audio.volume = getLogarithmicVolume(this.volume)

                    this.audio.src = url

                    this.audio.load()
                    this.audio.play()
                })

                this.$eventHub.$emit('player_playing', url)
            },
            stop () {
                if (!this.isPlaying) {
                    return
                }

                this.$eventHub.$emit('player_stopped', this.audio.src)

                this.audio.pause()
                this.audio.src = ''

                this.isPlaying = false
            }
        }
    }
</script>
