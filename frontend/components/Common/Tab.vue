<template>
    <div
        v-if="isActive || !isLazy"
        v-show="isActive"
        :id="`${computedId}-content`"
    >
        <slot />
    </div>
</template>

<script setup lang="ts">
import {TabChildProps, useTabChild} from "~/functions/tabs.ts";

const props = defineProps<TabChildProps>();

const {computedId, isActive, isLazy} = useTabChild(props);
</script>
