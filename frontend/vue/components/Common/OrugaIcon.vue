<template>
    <icon
        :icon="iconName"
        :class="size"
    />
</template>
<script setup lang="ts">
import {computed} from "vue";
import Icon from "~/components/Common/Icon.vue";

const props = defineProps({
    icon: {
        type: Array,
        required: true
    },
    size: {
        type: String,
        default: null
    }
});

const iconName = computed(() => props.icon[1]);
</script>
