<template>
    <code-mirror
        v-model="textValue"
        basic
        :lang="lang"
        :dark="isDark"
    />
</template>

<script setup lang="ts">
import CodeMirror from "vue-codemirror6";
import {useVModel} from "@vueuse/core";
import {computed} from "vue";
import {css} from "@codemirror/lang-css";
import {javascript} from "@codemirror/lang-javascript";
import useTheme from "~/functions/theme";
import {liquidsoap} from "~/vendor/lang_liquidsoap.ts";

const props = defineProps({
    modelValue: {
        type: String,
        required: true
    },
    mode: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const textValue = useVModel(props, 'modelValue', emit);

const lang = computed(() => {
    switch (props.mode) {
        case 'css':
            return css();
        case 'javascript':
            return javascript();
        case 'liquidsoap':
            return liquidsoap();
        default:
            return null;
    }
});

const {isDark} = useTheme();
</script>
