<template>
    <small
        class="badge badge-pill ml-2"
        :class="badgeClass"
    >{{ badgeText }}</small>
</template>

<script setup>
import {computed} from "vue";
import {useGettext} from "vue3-gettext";

const props = defineProps({
    enabled: {
        type: Boolean,
        required: true
    }
});

const badgeClass = computed(() => {
    return (props.enabled)
        ? 'badge-success'
        : 'badge-danger';
});

const {$gettext} = useGettext();

const badgeText = computed(() => {
    return (props.enabled)
        ? $gettext('Enabled')
        : $gettext('Disabled');
});
</script>
