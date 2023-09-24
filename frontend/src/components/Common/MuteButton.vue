<template>
    <button
        type="button"
        class="btn"
        :aria-label="muteLang"
        @click="toggleMute"
    >
        <icon :icon="muteIcon" />
    </button>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {computed, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {IconVolumeDown, IconVolumeOff, IconVolumeUp} from "~/components/Common/icons";

const props = defineProps({
    volume: {
        type: Number,
        required: true
    },
    isMuted: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['toggleMute']);

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
        return IconVolumeOff;
    }

    if (props.volume < 60) {
        return IconVolumeDown;
    }

    return IconVolumeUp;
});
</script>
