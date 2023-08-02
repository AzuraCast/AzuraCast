<template>
    <button
        type="button"
        :title="langTitle"
        :aria-label="langTitle"
        class="btn p-0"
        @click="toggle"
    >
        <icon
            class="outlined"
            :class="iconClass"
            :icon="iconText"
        />
    </button>
</template>

<script setup>
import Icon from "./Icon";
import {usePlayerStore} from "~/store";
import {computed, toRef} from "vue";
import {get} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";
import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery";

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

const $store = usePlayerStore();

const isPlaying = toRef($store, 'isPlaying');
const current = toRef($store, 'current');

const isThisPlaying = computed(() => {
    if (!get(isPlaying)) {
        return false;
    }

    const playingUrl = getUrlWithoutQuery(get(current).url);
    const thisUrl = getUrlWithoutQuery(props.url);
    return playingUrl === thisUrl;
});

const {$gettext} = useTranslate();

const langTitle = computed(() => {
    return get(isThisPlaying)
        ? $gettext('Stop')
        : $gettext('Play');
});

const iconText = computed(() => {
    return get(isThisPlaying)
        ? 'stop_circle'
        : 'play_circle';
});

const toggle = () => {
    $store.toggle({
        url: props.url,
        isStream: props.isStream,
        isHls: props.isHls
    });
};

defineExpose({
    current,
    isPlaying,
    isThisPlaying,
    toggle
})
</script>
