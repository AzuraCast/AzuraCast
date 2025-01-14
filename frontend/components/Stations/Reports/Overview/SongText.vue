<template>
    <span v-if="song.title !== ''">
        <b>{{ song.title }}</b><br>
        {{ song.artist }}
    </span>
    <span v-else>
        {{ song.text }}
    </span>
</template>

<script setup lang="ts">
import {ApiHasSongFields} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    song: ApiHasSongFields
}>();
</script>
