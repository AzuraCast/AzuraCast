<template>
    <div class="d-flex flex-column gap-1">
        <meter
            v-for="(level, channel) in props.levels"
            :key="channel"
            class="w-100"
            min="-60"
            max="0"
            low="-18"
            high="-6"
            optimum="-60"
            :value="level"
        />
    </div>
</template>

<script setup lang="ts">
import { WebcasterLevels } from "~/components/Public/WebDJ/useWebcasterMixer.ts";

const props = defineProps<{
    levels: WebcasterLevels;
}>();
</script>
