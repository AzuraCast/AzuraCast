<template>
    <button
        type="button"
        :title="langTitle"
        :aria-label="langTitle"
        class="btn p-0"
        @click="toggle"
    >
        <icon
            :class="iconClass"
            :icon="iconText"
        />
    </button>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {IconPlayCircle, IconStopCircle} from "~/components/Common/icons";
import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery.ts";
import {
    blankStreamDescriptor,
    FullStreamDescriptor,
    StreamDescriptor,
    usePlayerStore
} from "~/functions/usePlayerStore.ts";
import {storeToRefs} from "pinia";

const props = defineProps<{
    stream: StreamDescriptor,
    iconClass?: string
}>();

const playerStore = usePlayerStore();
const {isPlaying, current} = storeToRefs(playerStore);
const {toggle: storeToggle} = playerStore;

const isThisPlaying = computed(() => {
    if (!isPlaying.value) {
        return false;
    }

    const streamWithDefaults: FullStreamDescriptor = {
        ...blankStreamDescriptor,
        ...props.stream,
    };

    if (streamWithDefaults.channel !== current.value.channel) {
        return false;
    }

    const playingUrl = getUrlWithoutQuery(current.value.url);
    const thisUrl = getUrlWithoutQuery(streamWithDefaults.url);
    return playingUrl === thisUrl;
});

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return isThisPlaying.value
        ? $gettext('Stop')
        : $gettext('Play');
});

const iconText = computed(() => {
    return isThisPlaying.value
        ? IconStopCircle
        : IconPlayCircle;
});

const toggle = () => {
    storeToggle(props.stream);
};

defineExpose({
    toggle
})
</script>
