<template>
    <span
        class="playlist-source-icon d-inline-flex align-items-center"
        aria-hidden="true"
    >
        <component
            :is="icon"
            v-if="icon"
            :class="sizeClass"
        />
    </span>
</template>

<script setup lang="ts">
import { type Component, computed } from "vue";
import {
    isPlaylistSource,
    playlistSourceIcons,
} from "~/components/Stations/Common/playlistSourceIcons.ts";
import { IconSize } from "~/functions/icons.ts";

const props = defineProps<{
    source: string;
    size?: IconSize;
}>();

const icon = computed<Component | undefined>(() => {
    return isPlaylistSource(props.source)
        ? playlistSourceIcons[props.source]
        : undefined;
});

const sizeClass = computed<IconSize | undefined>(() => {
    return props.size && Object.values(IconSize).includes(props.size)
        ? props.size
        : undefined;
});
</script>
