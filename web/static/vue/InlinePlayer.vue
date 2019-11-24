<template>
    <div class="ml-3 player-inline" v-if="isPlaying">
        <a class="btn btn-sm px-2" href="#" @click.prevent="stop()">
            <i class="material-icons" aria-hidden="true">pause</i>
            <span class="sr-only" v-translate>Pause</span>
        </a>
        <div class="inline-volume-controls d-inline-flex align-items-center ml-1">
            <div class="flex-shrink-0">
                <a class="btn btn-sm px-2" href="#" @click.prevent="volume = 0">
                    <i class="material-icons" aria-hidden="true">volume_mute</i>
                    <span class="sr-only" v-translate>Mute</span>
                </a>
            </div>
            <div class="flex-fill mx-1">
                <input type="range" :title="lang_volume" class="player-volume-range custom-range" min="0" max="100"
                       step="1" v-model="volume">
            </div>
            <div class="flex-shrink-0">
                <a class="btn btn-sm px-2" href="#" @click.prevent="volume = 100">
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

  export default {
    data () {
      return {
        'isPlaying': false,
        'volume': 55,
        'audio': null
      }
    },
    created () {
      this.audio = document.createElement('audio')

      this.audio.onended = () => {
        this.stop()
      }

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
          this.play(url)
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
        this.audio.volume = Math.min((Math.exp(volume / 100) - 1) / (Math.E - 1), 1)

        if (store.enabled) {
          store.set('player_volume', volume)
        }
      }
    },
    methods: {
      play (url) {
        this.audio.src = url

        this.audio.load()
        this.audio.play()

        this.isPlaying = true

        this.$eventHub.$emit('player_playing', url)
      },
      stop () {
        this.isPlaying = false
        this.$eventHub.$emit('player_stopped', this.audio.src)

        this.audio.pause()
        this.audio.src = ''

        setTimeout(() => {
          this.audio.load()
        })
      }
    }
  }
</script>
