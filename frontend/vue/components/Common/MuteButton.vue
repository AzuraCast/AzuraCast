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

<script setup>
import Icon from "~/components/Common/Icon.vue";
import {computed, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";

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
        return 'volume_off';
    }

    if (props.volume < 60) {
        return 'volume_down';
    }

    return 'volume_up';
});
</script>
