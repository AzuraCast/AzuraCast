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

const props = withDefaults(
    defineProps<TabChildProps>(),
    {
        id: null,
        itemHeaderClass: null,
    }
);

const {computedId, isActive, isLazy} = useTabChild(props);
</script>
