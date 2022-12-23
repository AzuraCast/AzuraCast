<template>
    <code-mirror basic v-model="textValue" :lang="lang" :dark="dark"></code-mirror>
</template>

<script setup>
import CodeMirror from "vue-codemirror6";
import {useVModel} from "@vueuse/core";
import {computed} from "vue";
import {css} from "@codemirror/lang-css";
import {javascript} from "@codemirror/lang-javascript";
import {useAzuraCast} from "~/vendor/azuracast";

const props = defineProps({
    modelValue: String,
    mode: String
});

const emit = defineEmits(['update:modelValue']);

const textValue = useVModel(props, 'modelValue', emit);

const lang = computed(() => {
    if (props.mode === 'css') {
        return css();
    } else if (props.mode === 'javascript') {
        return javascript();
    }
    return null;
});

const {theme} = useAzuraCast();

const dark = computed(() => {
    return theme === 'dark';
})
</script>

<script>
export default {
    model: {
        prop: 'modelValue',
        event: 'update:modelValue'
    },
};
</script>
