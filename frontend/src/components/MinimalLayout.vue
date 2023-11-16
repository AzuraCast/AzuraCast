<template>
    <slot />
</template>

<script setup lang="ts">
import {useProvidePlayerStore} from "~/functions/usePlayerStore.ts";

useProvidePlayerStore('global');
</script>
