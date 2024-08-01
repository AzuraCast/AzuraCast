<template>
    <loading :loading="isLoading">
        <form-group-checkbox
            id="modal_scroll_to_bottom"
            v-model="scrollToBottom"
            :label="$gettext('Automatically Scroll to Bottom')"
        />

        <textarea
            id="log-view-contents"
            ref="$textarea"
            class="form-control log-viewer"
            spellcheck="false"
            readonly
            :value="logs"
        />
    </loading>
</template>

<script setup lang="ts">
import {nextTick, ref, toRef, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import {tryOnScopeDispose} from "@vueuse/core";
import Loading from "~/components/Common/Loading.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";

const props = defineProps({
    logUrl: {
        type: String,
        required: true,
    }
});

const isLoading = ref(false);
const logs = ref('');
const currentLogPosition = ref(null);
const scrollToBottom = ref(true);

const {axios} = useAxios();

const $textarea = ref<HTMLTextAreaElement | null>(null);

let updateInterval = null;

const stop = () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
};

tryOnScopeDispose(stop);

const updateLogs = () => {
    axios({
        method: 'GET',
        url: props.logUrl,
        params: {
            position: currentLogPosition.value
        }
    }).then((resp) => {
        if (resp.data.contents !== '') {
            logs.value = logs.value + resp.data.contents + "\n";
            if (scrollToBottom.value && $textarea.value) {
                nextTick(() => {
                    $textarea.value.scrollTop = $textarea.value?.scrollHeight;
                });
            }
        }

        currentLogPosition.value = resp.data.position;

        if (resp.data.eof) {
            stop();
        }
    }).finally(() => {
        isLoading.value = false;
    });
};

watch(toRef(props, 'logUrl'), (newLogUrl) => {
    isLoading.value = true;
    logs.value = '';
    currentLogPosition.value = 0;
    stop();

    if (null !== newLogUrl) {
        updateInterval = setInterval(updateLogs, 2500);
        updateLogs();
    }
}, {immediate: true});

const getContents = () => {
    return logs.value;
};

defineExpose({
    getContents
});
</script>
