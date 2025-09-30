<template>
    <button
        type="button"
        class="btn"
        :aria-label="muteLang"
        @click="toggleMute"
    >
        <component :is="muteIcon"/>
    </button>
</template>

<script setup lang="ts">
import {computed, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import IconIcVolumeDown from "~icons/ic/baseline-volume-down";
import IconIcVolumeOff from "~icons/ic/baseline-volume-off";
import IconIcVolumeUp from "~icons/ic/baseline-volume-up";

const props = defineProps<{
    volume: number,
    isMuted: boolean,
}>();

const emit = defineEmits<{
    (e: 'toggleMute'): void
}>();

watch(toRef(props, 'volume'), (newVol) => {
    const newMuted = (newVol === 0);

    if (props.isMuted !== newMuted) {
        emit('toggleMute');
    }
});

const {$gettext} = useTranslate();

const toggleMute = () => {
    emit('toggleMute');
};

const muteLang = computed(() => {
    return (props.isMuted)
        ? $gettext('Unmute')
        : $gettext('Mute')
});

const muteIcon = computed(() => {
    if (props.isMuted) {
        return IconIcVolumeOff;
    }

    if (props.volume < 60) {
        return IconIcVolumeDown;
    }

    return IconIcVolumeUp;
});
</script>
