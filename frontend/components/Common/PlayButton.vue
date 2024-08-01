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
import Icon from "./Icon.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {IconPlayCircle, IconStopCircle} from "~/components/Common/icons";
import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery.ts";
import {usePlayerStore} from "~/functions/usePlayerStore.ts";

const props = defineProps({
    url: {
        type: String,
        required: true
    },
    isStream: {
        type: Boolean,
        default: false
    },
    isHls: {
        type: Boolean,
        default: false
    },
    iconClass: {
        type: String,
        default: null
    }
});

const {isPlaying, current, toggle: storeToggle} = usePlayerStore();

const isThisPlaying = computed(() => {
    if (!isPlaying.value) {
        return false;
    }

    const playingUrl = getUrlWithoutQuery(current.value?.url);
    const thisUrl = getUrlWithoutQuery(props.url);
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
    storeToggle({
        url: props.url,
        isStream: props.isStream,
        isHls: props.isHls
    });
};

defineExpose({
    toggle
})
</script>
