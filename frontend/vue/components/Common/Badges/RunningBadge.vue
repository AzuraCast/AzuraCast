<template>
    <small class="badge badge-pill ml-2" :class="badgeClass">{{ badgeText }}</small>
</template>

<script setup>
import {computed} from "vue";
import {useGettext} from "vue3-gettext";

const props = defineProps({
    running: Boolean
});

const badgeClass = computed(() => {
    return (props.running)
        ? 'badge-success'
        : 'badge-danger';
});

const {$gettext} = useGettext();

const badgeText = computed(() => {
    return (props.running)
        ? $gettext('Running')
        : $gettext('Not Running');
});
</script>
