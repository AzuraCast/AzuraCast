<template>
    <audio-player
        ref="$player"
        :volume="volume"
        :is-muted="isMuted"
        @update:duration="onUpdateDuration"
        @update:current-time="onUpdateCurrentTime"
        @update:progress="onUpdateProgress"
    />

    <div
        v-if="isPlaying"
        v-bind="$attrs"
        class="player-inline"
    >
        <div
            v-if="!current.isStream && duration !== 0"
            class="inline-seek d-inline-flex align-items-center ms-1"
        >
            <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                {{ currentTimeText }}
            </div>
            <div class="flex-fill mx-2">
                <input
                    v-model="progress"
                    type="range"
                    :title="$gettext('Seek')"
                    class="player-seek-range form-range"
                    min="0"
                    max="100"
                    step="1"
                >
            </div>
            <div class="flex-shrink-0 mx-1 text-white-50 time-display">
                {{ durationText }}
            </div>
        </div>

        <button
            type="button"
            class="btn p-2 ms-2 text-reset"
            :aria-label="$gettext('Stop')"
            @click="stop()"
        >
            <icon :icon="IconStop" />
        </button>
        <div class="inline-volume-controls d-inline-flex align-items-center ms-2">
            <div class="flex-shrink-0">
                <mute-button
                    class="btn p-2 text-reset"
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
                    class="player-volume-range form-range"
                    min="0"
                    max="100"
                    step="1"
                >
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import AudioPlayer from '~/components/Common/AudioPlayer.vue';
import formatTime from '~/functions/formatTime';
import Icon from '~/components/Common/Icon.vue';
import {computed, Ref, ref} from "vue";
import MuteButton from "~/components/Common/MuteButton.vue";
import usePlayerVolume from "~/functions/usePlayerVolume";
import {IconStop} from "~/components/Common/icons";
import {usePlayerStore} from "~/functions/usePlayerStore.ts";

defineOptions({
    inheritAttrs: false
});

const {isPlaying, current, stop} = usePlayerStore();

const volume = usePlayerVolume();
const isMuted = ref(false);

const duration: Ref<number> = ref(0);
const currentTime: Ref<number> = ref(0);
const rawProgress: Ref<number> = ref(0);

const onUpdateDuration = (newValue: number) => {
    duration.value = newValue;
};

const onUpdateCurrentTime = (newValue: number) => {
    console.log(newValue);
    currentTime.value = newValue;
};

const onUpdateProgress = (newValue: number) => {
    rawProgress.value = newValue;
};

const durationText = computed(() => formatTime(duration.value));
const currentTimeText = computed(() => formatTime(currentTime.value));

const $player = ref<InstanceType<typeof AudioPlayer> | null>(null);

const progress = computed({
    get: () => {
        return rawProgress.value;
    },
    set: (prog) => {
        $player.value?.setProgress(prog);
        rawProgress.value = prog;
    }
});

const toggleMute = () => {
    isMuted.value = !isMuted.value;
};

defineExpose({
    stop
});
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
        width: 125px;
    }

    input.player-volume-range,
    input.player-seek-range {
        width: 100%;
        height: 10px;
    }
}
</style>
