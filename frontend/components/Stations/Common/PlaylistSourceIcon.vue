<template>
    <span
        class="playlist-source-icon d-inline-flex align-items-center"
        aria-hidden="true"
    >
        <template v-if="source === PlaylistSources.Songs">
            <icon-ic-library-music :class="sizeClass" />
        </template>
        <template v-else-if="source === PlaylistSources.Playlists">
            <icon-ic-queue-music :class="sizeClass" />
        </template>
        <template v-else-if="source === PlaylistSources.Requests">
            <icon-bi-people :class="sizeClass" />
        </template>
        <template v-else-if="source === PlaylistSources.RemoteUrl">
            <icon-ic-public :class="sizeClass" />
        </template>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { PlaylistSources } from "~/entities/ApiInterfaces.ts";
import { IconSize } from "~/functions/icons.ts";
import IconBiPeople from "~icons/bi/people";
import IconIcLibraryMusic from "~icons/ic/baseline-library-music";
import IconIcPublic from "~icons/ic/baseline-public";
import IconIcQueueMusic from "~icons/ic/baseline-queue-music";

const props = defineProps<{
    source: string;
    size?: IconSize;
}>();

const sizeClass = computed<IconSize | undefined>(() =>
    props.size && Object.values(IconSize).includes(props.size)
        ? props.size
        : undefined,
);
</script>
