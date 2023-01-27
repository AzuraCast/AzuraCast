<template>
    <button
        ref="btn"
        class="btn btn-copy btn-link btn-xs"
        :aria-label="$gettext('Copy to Clipboard')"
        @click.prevent="doCopy"
    >
        <icon
            class="sm"
            icon="file_copy"
        />
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
