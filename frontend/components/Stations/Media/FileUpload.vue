<template>
    <flow-upload
        :target-url="targetUrl"
        :valid-mime-types="validMimeTypes"
        allow-multiple
        @complete="onFlowUpload"
        @error="onFlowUpload"
    />
</template>

<script setup lang="ts">
import FlowUpload from '~/components/Common/FlowUpload.vue';
import {computed} from "vue";

const props = defineProps({
    uploadUrl: {
        type: String,
        required: true
    },
    currentDirectory: {
        type: String,
        required: true
    },
    searchPhrase: {
        type: String,
        required: true
    },
    validMimeTypes: {
        type: Array,
        default() {
            return ['audio/*'];
        }
    }
});

const emit = defineEmits(['relist']);

const targetUrl = computed(() => {
    const url = new URL(props.uploadUrl, document.location.href);
    url.searchParams.set('currentDirectory', props.currentDirectory);
    url.searchParams.set('searchPhrase', props.searchPhrase);

    return url.toString();
});

const onFlowUpload = () => {
    emit('relist');
}
</script>
