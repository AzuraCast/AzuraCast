<template>
    <span
        class="playlist-source-icon d-inline-flex align-items-center"
        :title="label"
        :aria-label="label"
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
import { useTranslate } from "~/vendor/gettext";
import IconBiPeople from "~icons/bi/people";
import IconIcLibraryMusic from "~icons/ic/baseline-library-music";
import IconIcPublic from "~icons/ic/baseline-public";
import IconIcQueueMusic from "~icons/ic/baseline-queue-music";

const props = defineProps<{
    source: string;
    size?: IconSize;
}>();

const { $gettext } = useTranslate();

const sizeClass = computed<IconSize | undefined>(() =>
    props.size && Object.values(IconSize).includes(props.size)
        ? props.size
        : undefined,
);

const label = computed<string>(() => {
    switch (props.source) {
        case PlaylistSources.Songs:
            return $gettext("Song-based");
        case PlaylistSources.Playlists:
            return $gettext("Playlist Group");
        case PlaylistSources.Requests:
            return $gettext("Request Queue");
        case PlaylistSources.RemoteUrl:
            return $gettext("Remote URL");
        default:
            return "";
    }
});
</script>
