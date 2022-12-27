<template>
    <button ref="btn" class="btn btn-copy btn-link btn-xs" @click.prevent="doCopy"
            :aria-label="$gettext('Copy to Clipboard')">
        <icon class="sm" icon="file_copy"></icon>
        <span v-if="!hideText">{{ $gettext('Copy to Clipboard') }}</span>
    </button>
</template>

<script setup>
import Icon from "~/components/Common/Icon.vue";
import {useClipboard} from "@vueuse/core";

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

const clipboard = useClipboard({legacy: true});

const doCopy = () => {
    clipboard.copy(props.text);
};
</script>
