<template>
    <button ref="btn" class="btn btn-copy btn-link btn-xs" @click.prevent="doCopy">
        <icon class="sm" icon="file_copy"></icon>
        <span :class="{ 'sr-only': hideText }" key="lang_copy_to_clipboard" v-translate>Copy to Clipboard</span>
    </button>
</template>

<script setup>
import Icon from "~/components/Common/Icon.vue";
import {copyToClipboard} from "~/vendor/clipboard";

const props = defineProps({
    text: {
        type: String,
        required: true,
    },
    hideText: {
        type: Boolean,
        default: false
    }
});

const doCopy = () => {
    copyToClipboard(props.text);
};
</script>
