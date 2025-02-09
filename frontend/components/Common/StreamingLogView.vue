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
import {nextTick, ref, toRef, useTemplateRef, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import {tryOnScopeDispose} from "@vueuse/core";
import Loading from "~/components/Common/Loading.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";

const props = defineProps<{
    logUrl: string
}>();

const isLoading = ref<boolean>(false);
const logs = ref<string>('');
const currentLogPosition = ref<number | null>(null);
const scrollToBottom = ref<boolean>(true);

const {axios} = useAxios();

const $textarea = useTemplateRef('$textarea');

let updateInterval: ReturnType<typeof setInterval> | null = null;

const stop = () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
};

tryOnScopeDispose(stop);

const updateLogs = () => {
    void axios({
        method: 'GET',
        url: props.logUrl,
        params: {
            position: currentLogPosition.value
        }
    }).then((resp) => {
        if (resp.data.contents !== '') {
            logs.value = logs.value + resp.data.contents + "\n";
            if (scrollToBottom.value && $textarea.value) {
                void nextTick(() => {
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

    if ('' !== newLogUrl) {
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
