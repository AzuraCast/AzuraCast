<template>
    <template
        v-for="(playlist, index) in playlists"
        :key="playlist.id"
    >
        <a
            v-if="playlist.folder"
            class="btn-search text-nowrap"
            href="#"
            :title="$gettext(
                'This playlist is assigned from the folder %{folder}. Click to view tracks in playlist',
                {
                    folder: playlist.folder
                }
            )"
            @click.prevent="emit('filter', 'playlist:'+playlist.short_name)"
        >
            <icon-ic-folder class="sm me-1"/>

            <span class="text-wrap">
                {{ playlist.name }}
            </span>
        </a>
        <a
            v-else
            class="btn-search"
            href="#"
            :title="$gettext('View tracks in playlist')"
            @click.prevent="emit('filter', 'playlist:'+playlist.short_name)"
        >
            {{ playlist.name }}
        </a>

        <span v-if="index+1 < playlists.length">, </span>
    </template>
</template>

<script setup lang="ts">
import {ApiStationMediaPlaylist} from "~/entities/ApiInterfaces.ts";
import IconIcFolder from "~icons/ic/baseline-folder";

defineProps<{
    playlists: ApiStationMediaPlaylist[]
}>()

const emit = defineEmits<{
    (e: 'filter', filter: string): void
}>();
</script>
