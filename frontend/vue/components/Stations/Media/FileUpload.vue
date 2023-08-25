<template>
    <flow-upload
        :target-url="uploadUrl"
        :flow-configuration="flowConfiguration"
        :valid-mime-types="validMimeTypes"
        allow-multiple
        @complete="onFlowUpload"
        @error="onFlowUpload"
    />
</template>

<script setup lang="ts">
import FlowUpload from '~/components/Common/FlowUpload.vue';

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

const flowConfiguration = {
    query: () => {
        return {
            'currentDirectory': props.currentDirectory,
            'searchPhrase': props.searchPhrase
        };
    }
};

const onFlowUpload = () => {
    emit('relist');
}
</script>
