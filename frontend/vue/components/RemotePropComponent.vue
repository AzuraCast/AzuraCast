<template>
    <async-comp v-bind="state" />
</template>
<script setup>
import {defineAsyncComponent} from "vue";

const props = defineProps({
    component: {
        type: Function,
        required: true
    },
    state: {
        type: Object,
        required: true
    }
});

const AsyncComp = defineAsyncComponent(props.component);
</script>
