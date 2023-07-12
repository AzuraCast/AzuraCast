<template>
    <button
        ref="btn"
        type="button"
        class="btn btn-copy btn-link btn-xs"
        :aria-label="$gettext('Copy to Clipboard')"
        @click.prevent="doCopy"
    >
        <icon
            class="sm"
            icon="file_copy"
        />
        <span v-if="!hideText">{{ copyText }}</span>
    </button>
</template>

<script setup>
import Icon from "~/components/Common/Icon.vue";
import {refAutoReset, useClipboard} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";

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

const {$gettext} = useTranslate();

const copyText = refAutoReset(
    $gettext('Copy to Clipboard'),
    1000
);

const clipboard = useClipboard({legacy: true});

const doCopy = () => {
    clipboard.copy(props.text);
    copyText.value = $gettext('Copied!');
};
</script>
