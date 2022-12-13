<template>
    <textarea ref="textarea" spellcheck="false" v-model="textValue"/>
</template>

<script setup>
import Codemirror from 'codemirror';
import 'codemirror/lib/codemirror.css';
import 'codemirror/mode/css/css.js';
import 'codemirror/mode/javascript/javascript.js';
import {get, set, templateRef, useVModel} from "@vueuse/core";
import {nextTick, onMounted, onUnmounted, ref, watch} from "vue";

const props = defineProps({
    modelValue: String,
    mode: String
});

const emit = defineEmits(['update:modelValue']);

const textValue = useVModel(props, 'modelValue', emit);

const $textarea = templateRef('textarea');
const content = ref(null);
let codemirror = null;

watch(textValue, (newVal) => {
    newVal = newVal || '';

    const cm_value = (codemirror !== null)
        ? codemirror.getValue()
        : null;

    if (newVal !== cm_value) {
        set(content, newVal);

        if (codemirror !== null) {
            codemirror.setValue(newVal);
        }
    }
});

const refresh = () => {
    nextTick(() => {
        if (codemirror !== null) {
            codemirror.refresh();
        }
    });
};

onMounted(() => {
    codemirror = Codemirror.fromTextArea(
        get($textarea),
        {
            lineNumbers: true,
            theme: 'default',
            mode: props.mode
        }
    );

    set(content, props.value || '');

    codemirror.setValue(get(content));
    codemirror.on('change', cm => {
        emit('update:modelValue', cm.getValue());
    });

    refresh();
});

onUnmounted(() => {
    const element = codemirror.doc.cm.getWrapperElement();
    element && element.remove && element.remove();
});
</script>

<script>
export default {
    model: {
        prop: 'modelValue',
        event: 'update:modelValue'
    },
};
</script>
