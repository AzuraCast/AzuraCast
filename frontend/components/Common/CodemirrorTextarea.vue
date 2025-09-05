<template>
    <code-mirror
        v-model="textValue"
        basic
        :lang="lang ?? undefined"
        :dark="isDark"
    />
</template>

<script setup lang="ts">
import CodeMirror from "vue-codemirror6";
import {computed} from "vue";
import {css} from "@codemirror/lang-css";
import {javascript} from "@codemirror/lang-javascript";
import {html} from "@codemirror/lang-html";
import {liquidsoap} from "codemirror-lang-liquidsoap";
import {storeToRefs} from "pinia";
import {useTheme} from "~/functions/theme.ts";

const props = defineProps<{
    modelValue?: string | number | null,
    mode: string
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', modelValue: string | null): void
}>();

const textValue = computed({
    get() {
        const value = props.modelValue ?? null;
        if (value === null) {
            return "";
        }

        return String(value);
    },
    set(newValue) {
        emit('update:modelValue', newValue);
    }
});

const lang = computed(() => {
    switch (props.mode) {
        case 'css':
            return css();
        case 'javascript':
            return javascript();
        case 'html':
            return html();
        case 'liquidsoap':
            return liquidsoap();
        default:
            return null;
    }
});

const {isDark} = storeToRefs(useTheme());
</script>
