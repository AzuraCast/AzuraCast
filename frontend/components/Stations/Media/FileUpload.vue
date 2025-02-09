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
import FlowUpload from "~/components/Common/FlowUpload.vue";
import {computed} from "vue";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";

const props = withDefaults(
    defineProps<{
        uploadUrl: string,
        currentDirectory: string,
        searchPhrase: string,
        validMimeTypes?: string[]
    }>(),
    {
        validMimeTypes: () => ['audio/*']
    }
);

const emit = defineEmits<HasRelistEmit>();

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
