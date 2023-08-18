<template>
    <loading :loading="isLoading">
        <div style="max-height: 300px; overflow-y: scroll;">
            <code-mirror
                id="log-view-contents"
                v-model="logs"
                readonly
                basic
                :dark="isDark"
            />
        </div>
    </loading>
</template>

<script setup>
import {ref, toRef, watch} from "vue";
import {useAxios} from "~/vendor/axios";
import Loading from "~/components/Common/Loading.vue";
import CodeMirror from "vue-codemirror6";
import useTheme from "~/functions/theme";

const props = defineProps({
    logUrl: {
        type: String,
        required: true,
    }
});

const isLoading = ref(false);
const logs = ref('');

const {isDark} = useTheme();

const {axios} = useAxios();

watch(toRef(props, 'logUrl'), (newLogUrl) => {
    isLoading.value = true;
    logs.value = '';

    if (null !== newLogUrl) {
        axios({
            method: 'GET',
            url: props.logUrl
        }).then((resp) => {
            if (resp.data.contents !== '') {
                logs.value = resp.data.contents;
            }
        }).finally(() => {
            isLoading.value = false;
        });
    }
}, {immediate: true});

const getContents = () => {
    return logs.value;
};

defineExpose({
    getContents
});
</script>
