<template>
    <div class="dropdown ml-3 player-inline" v-if="is_playing">
        <button aria-expanded="false" aria-haspopup="true" class="navbar-toggler" data-toggle="dropdown" type="button">
            <i class="material-icons" aria-hidden="true">radio</i>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li>
                <a href="#" class="dropdown-item jp-pause" @click.prevent="stop()">
                    <i class="material-icons" aria-hidden="true">pause</i>
                    {{ $t('pause') }}
                </a>
            </li>
            <li>
            <span class="dropdown-item dropdown-item-text">
                <i class="material-icons" aria-hidden="true">volume_up</i>
                <input type="range" :title="$t('volume')" class="player-volume-range custom-range" min="0" max="100" step="1" v-model="volume">
            </span>
            </li>
        </ul>
    </div>
</template>

<style lang="scss">
.player-inline {
    .player-volume-range {
        width: 100px;
    }
}
</style>

<script>
import store from 'store';

export default {
    data: function() {
        return {
            "is_playing": false,
            "volume": 55,
            "audio": null
        };
    },
    created: function() {
        this.audio = document.createElement('audio');

        this.audio.onended = () => {
            this.stop();
        };

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
            if (this.is_playing && this.audio.src === url) {
                this.stop();
            } else {
                this.play(url);
            }
        });
    },
    watch: {
        "volume": function(volume) {
            this.audio.volume = Math.min((Math.exp(volume/100)-1)/(Math.E-1), 1);

            if (store.enabled) {
                store.set('player_volume', volume);
            }
        },
    },
    methods: {
        "play": function(url) {
            this.audio.src = url;

            this.audio.load();
            this.audio.play();

            this.is_playing = true;

            this.$eventHub.$emit('player_playing', url);
        },
        "stop": function() {
            this.is_playing = false;
            this.$eventHub.$emit('player_stopped', this.audio.src);

            this.audio.pause();
            this.audio.src = '';

            setTimeout(() => {
                this.audio.load();
            });
        }
    }
}
</script>
