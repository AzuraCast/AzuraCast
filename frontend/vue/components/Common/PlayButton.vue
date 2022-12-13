<template>
    <a href="#" @click.prevent="toggle" :title="langTitle">
        <icon :class="iconClass" :icon="iconText"></icon>
    </a>
</template>

<script setup>
import Icon from "./Icon";
import {usePlayerStore} from "~/store";
import {computed} from "vue";
import {get} from "@vueuse/core";
import gettext from "~/vendor/gettext";
import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery";

const props = defineProps({
    url: String,
    isStream: {
        type: Boolean,
        default: false
    },
    isHls: {
        type: Boolean,
        default: false
    },
    iconClass: String
});

const $store = usePlayerStore();
const {$gettext} = gettext;

const isPlaying = computed(() => {
    return $store.isPlaying;
});

const current = computed(() => {
    return $store.current;
});

const isThisPlaying = computed(() => {
    if (!get(isPlaying)) {
        return false;
    }

    let playingUrl = getUrlWithoutQuery(get(current).url);
    let thisUrl = getUrlWithoutQuery(props.url);
    return playingUrl === thisUrl;
});

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
