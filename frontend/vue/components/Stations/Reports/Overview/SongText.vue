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
const props = defineProps({
    song: {
        type: Object,
        required: true
    }
});
</script>
