<template>
    <audio-player ref="player" :volume="volume" :is-muted="isMuted"></audio-player>

    <div class="ml-3 player-inline" v-if="isPlaying">
        <div class="inline-seek d-inline-flex align-items-center ml-1" v-if="!current.isStream && duration !== 0">
            <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                {{ currentTimeText }}
            </div>
            <div class="flex-fill mx-2">
                <input type="range" :title="$gettext('Seek')" class="player-seek-range custom-range" min="0"
                       max="100"
                       step="1" v-model="progress">
            </div>
            <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                {{ durationText }}
            </div>
        </div>

        <a class="btn btn-sm btn-outline-light px-2 ml-1" href="#" @click.prevent="stop()"
           :aria-label="$gettext('Stop')">
            <icon icon="stop"></icon>
        </a>
        <div class="inline-volume-controls d-inline-flex align-items-center ml-1">
            <div class="flex-shrink-0">
                <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="mute"
                   :aria-label="$gettext('Mute')">
                    <icon icon="volume_mute"></icon>
                </a>
            </div>
            <div class="flex-fill mx-1">
                <input type="range" :title="$gettext('Volume')" class="player-volume-range custom-range" min="0"
                       max="100"
                       step="1" v-model="volume">
            </div>
            <div class="flex-shrink-0">
                <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="fullVolume"
                   :aria-label="$gettext('Full Volume')">
                    <icon icon="volume_up"></icon>
                </a>
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

<script setup>
import AudioPlayer from '~/components/Common/AudioPlayer.vue';
import formatTime from '~/functions/formatTime.js';
import Icon from '~/components/Common/Icon.vue';
import {usePlayerStore} from "~/store.js";
import {useMounted, useStorage} from "@vueuse/core";
import {computed, ref, toRef} from "vue";

const store = usePlayerStore();
const isPlaying = toRef(store, 'isPlaying');
const current = toRef(store, 'current');

const volume = useStorage('player_volume', 55);
const isMuted = useStorage('player_is_muted', false);
const isMounted = useMounted();
const player = ref(); // AudioPlayer

const duration = computed(() => {
    return player.value?.getDuration();
});

const durationText = computed(() => {
    return formatTime(duration.value);
});

const currentTime = computed(() => {
    return player.value?.getCurrentTime();
});

const currentTimeText = computed(() => {
    return formatTime(currentTime.value);
});

const progress = computed({
    get: () => {
        return player.value?.getProgress();
    },
    set: (prog) => {
        player.value?.setProgress(prog);
    }
});

const stop = () => {
    store.toggle({
        url: null,
        isStream: true,
        isHls: false,
    });
};

const mute = () => {
    isMuted.value = !isMuted.value;
};

const fullVolume = () => {
    volume.value = 100;
};
</script>
