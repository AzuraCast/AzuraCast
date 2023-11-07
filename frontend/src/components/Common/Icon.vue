<template>
    <svg
        class="icon"
        xmlns="http://www.w3.org/2000/svg"
        :viewBox="icon.viewBox"
        fill="currentColor"
        focusable="false"
        aria-hidden="true"
        v-html="icon.contents"
    />
</template>

<script setup lang="ts">
import {Icon} from "~/components/Common/icons.ts";

const props = defineProps<{
    icon: Icon
}>();
</script>
