<template>
    <div
        v-if="isCurrentChannel && isPlaying"
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
                    v-model.number="seekPosition"
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
            <icon-ic-stop/>
        </button>
        <div
            v-if="showVolume"
            class="inline-volume-controls d-inline-flex align-items-center ms-2"
        >
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
import {computed} from "vue";
import MuteButton from "~/components/Common/Audio/MuteButton.vue";
import {StreamChannel, usePlayerStore} from "~/functions/usePlayerStore.ts";
import {storeToRefs} from "pinia";
import IconIcStop from "~icons/ic/baseline-stop";

defineOptions({
    inheritAttrs: false
});

const props = withDefaults(
    defineProps<{
        channel?: StreamChannel
    }>(),
    {
        channel: StreamChannel.Global
    }
);

const playerStore = usePlayerStore();
const {
    isPlaying,
    current,
    duration,
    durationText,
    currentTimeText,
    volume,
    showVolume,
    isMuted,
    progress
} = storeToRefs(playerStore);
const {stop, seek, toggleMute} = playerStore;

const isCurrentChannel = computed(
    () => props.channel === current.value.channel
);

const seekPosition = computed({
    get: () => progress.value.position,
    set: (prog) => {
        seek(prog);
    }
});

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
