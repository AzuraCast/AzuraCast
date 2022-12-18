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
                <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="volume = 0"
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
                <a class="btn btn-sm btn-outline-light px-2" href="#" @click.prevent="volume = 100"
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
import AudioPlayer from '~/components/Common/AudioPlayer';
import formatTime from '~/functions/formatTime.js';
import Icon from '~/components/Common/Icon';
import {usePlayerStore} from "~/store.js";
import {get, set, useMounted, useStorage} from "@vueuse/core";
import {computed, ref, toRef} from "vue";

const store = usePlayerStore();
const isPlaying = toRef(store, 'isPlaying');
const current = toRef(store, 'current');

const volume = useStorage('player_volume', 55);
const isMuted = useStorage('player_is_muted', false);
const isMounted = useMounted();
const player = ref(); // Template ref

const duration = computed(() => {
    if (!get(isMounted)) {
        return;
    }

    return get(player).getDuration();
});

const durationText = computed(() => {
    return formatTime(get(duration));
});

const currentTime = computed(() => {
    if (!get(isMounted)) {
        return;
    }

    return get(player).getCurrentTime();
});

const currentTimeText = computed(() => {
    return formatTime(get(currentTime));
});

const progress = computed({
    get: () => {
        if (!get(isMounted)) {
            return;
        }

        return get(player).getProgress();
    },
    set: (prog) => {
        get(player).setProgress(prog);
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
    set(isMuted, !get(isMuted));
};
</script>
