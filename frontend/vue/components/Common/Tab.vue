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
import {useTabChild} from "~/functions/tabs.ts";

const props = defineProps({
    id: {
        type: [String, Number],
        default: null
    },
    label: {
        type: String,
        required: true
    },
    itemHeaderClass: {
        type: [String, Function, Array],
        default: () => null
    }
});

const {computedId, isActive, isLazy} = useTabChild(props);
</script>
