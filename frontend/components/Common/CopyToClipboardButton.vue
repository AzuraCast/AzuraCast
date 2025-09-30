<template>
    <button
        ref="btn"
        type="button"
        class="btn btn-copy btn-link btn-xs"
        :aria-label="$gettext('Copy to Clipboard')"
        @click.prevent="doCopy"
    >
        <icon-ic-copy/>

        <span v-if="!hideText">{{ copyText }}</span>
    </button>
</template>

<script setup lang="ts">
import {refAutoReset, useClipboard} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";

import IconIcCopy from "~icons/ic/baseline-content-copy";

const props = withDefaults(
    defineProps<{
        text: string,
        hideText?: boolean
    }>(),
    {
        hideText: false,
    }
);

const {$gettext} = useTranslate();

const copyText = refAutoReset(
    $gettext('Copy to Clipboard'),
    1000
);

const clipboard = useClipboard({legacy: true});

const doCopy = async () => {
    await clipboard.copy(props.text);
    copyText.value = $gettext('Copied!');
};
</script>
