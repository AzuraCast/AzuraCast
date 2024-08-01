<template>
    <small
        class="badge rounded-pill ms-2"
        :class="badgeClass"
    >{{ badgeText }}</small>
</template>

<script setup lang="ts">
import {computed} from "vue";
import {useGettext} from "vue3-gettext";

const props = defineProps({
    running: {
        type: Boolean,
        required: true
    }
});

const badgeClass = computed(() => {
    return (props.running)
        ? 'text-bg-success'
        : 'text-bg-danger';
});

const {$gettext} = useGettext();

const badgeText = computed(() => {
    return (props.running)
        ? $gettext('Running')
        : $gettext('Not Running');
});
</script>
