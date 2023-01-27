<template>
    <i
        :class="iconClass"
        aria-hidden="true"
    >{{ icon }}</i>
</template>

<script setup>
import {computed} from "vue";

const props = defineProps({
    type: {
        type: String,
        default: 'md'
    },
    icon: {
        type: String,
        required: true
    }
});

const iconClass = computed(() => {
    if (props.type === 'md') {
        return ['material-icons'];
    }
    return null;
});
</script>
