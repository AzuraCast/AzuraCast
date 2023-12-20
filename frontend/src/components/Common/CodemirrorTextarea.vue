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
import {liquidsoap} from "codemirror-lang-liquidsoap";
import useTheme from "~/functions/theme";

const props = defineProps<{
    modelValue: string | null,
    mode: string
}>();

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
