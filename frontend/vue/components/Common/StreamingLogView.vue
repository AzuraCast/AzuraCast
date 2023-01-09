<template>
    <b-overlay
        variant="card"
        :show="loading"
    >
        <b-form-group label-for="modal_scroll_to_bottom">
            <b-form-checkbox
                id="modal_scroll_to_bottom"
                v-model="scrollToBottom"
            >
                {{ $gettext('Automatically Scroll to Bottom') }}
            </b-form-checkbox>
        </b-form-group>

        <textarea
            id="log-view-contents"
            ref="$textarea"
            class="form-control log-viewer"
            spellcheck="false"
            readonly
            :value="logs"
        />
    </b-overlay>
</template>

<script setup>
import {nextTick, onMounted, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import {useTimeoutFn} from "@vueuse/core";

const props = defineProps({
    logUrl: {
        type: String,
        required: true,
    }
});

const loading = ref(false);
const logs = ref('');
const currentLogPosition = ref(null);
const scrollToBottom = ref(true);

const {axios} = useAxios();

const $textarea = ref(); // Template Ref

const scrollTextarea = () => {
    if (scrollToBottom.value) {
        nextTick(() => {
            $textarea.value.scrollTop = $textarea.value.scrollHeight;
        });
    }
};

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
            scrollTextarea();
        }

        currentLogPosition.value = resp.data.position;

        if (!resp.data.eof) {
            useTimeoutFn(updateLogs, 2500);
        }
    });
};

onMounted(() => {
    loading.value = true;

    axios({
        method: 'GET',
        url: props.logUrl
    }).then((resp) => {
        if (resp.data.contents !== '') {
            logs.value = resp.data.contents + "\n";
            scrollTextarea();
        } else {
            logs.value = '';
        }

        currentLogPosition.value = resp.data.position;

        if (!resp.data.eof) {
            useTimeoutFn(updateLogs, 2500);
        }
    }).finally(() => {
        loading.value = false;
    });
});

const getContents = () => {
    return logs.value;
};

defineExpose({
    getContents
});
</script>
