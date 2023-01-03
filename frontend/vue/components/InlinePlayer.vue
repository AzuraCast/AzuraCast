<template>
    <audio-player
        ref="$player"
        :volume="volume"
        :is-muted="isMuted"
    />

    <div
        v-if="isPlaying"
        class="ml-3 player-inline"
    >
        <div
            v-if="!current.isStream && duration !== 0"
            class="inline-seek d-inline-flex align-items-center ml-1"
        >
            <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                {{ currentTimeText }}
            </div>
            <div class="flex-fill mx-2">
                <input
                    v-model="progress"
                    type="range"
                    :title="$gettext('Seek')"
                    class="player-seek-range custom-range"
                    min="0"
                    max="100"
                    step="1"
                >
            </div>
            <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                {{ durationText }}
            </div>
        </div>

        <a
            class="btn btn-sm btn-outline-light px-2 ml-1"
            href="#"
            :aria-label="$gettext('Stop')"
            @click.prevent="stop()"
        >
            <icon icon="stop" />
        </a>
        <div class="inline-volume-controls d-inline-flex align-items-center ml-1">
            <div class="flex-shrink-0">
                <mute-button
                    class="btn btn-sm btn-outline-light px-2"
                    :volume="volume"
                    :is-muted="isMuted"
                    @toggle-mute="toggleMute"
                />
            </div>
            <div class="flex-fill mx-1">
                <input
                    v-model.number="volume"
                    type="range"
                    :title="$gettext('Volume')"
                    class="player-volume-range custom-range"
                    min="0"
                    max="100"
                    step="1"
                >
            </div>
        </div>
    </div>
</template>

<script setup>
import AudioPlayer from '~/components/Common/AudioPlayer.vue';
import formatTime from '~/functions/formatTime.js';
import Icon from '~/components/Common/Icon.vue';
import {usePlayerStore} from "~/store.js";
import {useStorage} from "@vueuse/core";
import {computed, ref, toRef} from "vue";
import MuteButton from "~/components/Common/MuteButton.vue";

const store = usePlayerStore();
const isPlaying = toRef(store, 'isPlaying');
const current = toRef(store, 'current');

const volume = useStorage('player_volume', 55);
const isMuted = useStorage('player_is_muted', false);
const $player = ref(); // AudioPlayer

const duration = computed(() => {
    return $player.value?.getDuration();
});

const durationText = computed(() => {
    return formatTime(duration.value);
});

const currentTime = computed(() => {
    return $player.value?.getCurrentTime();
});

const currentTimeText = computed(() => {
    return formatTime(currentTime.value);
});

const progress = computed({
    get: () => {
        return $player.value?.getProgress();
    },
    set: (prog) => {
        $player.value?.setProgress(prog);
    }
});

const stop = () => {
    store.toggle({
        url: null,
        isStream: true,
        isHls: false,
    });
};

const toggleMute = () => {
    isMuted.value = !isMuted.value;
};
</script>

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
