<template>
    <button ref="btn" class="btn btn-copy btn-link btn-xs" @click.prevent="doCopy">
        <icon class="sm" icon="file_copy"></icon>
        <span :class="{ 'sr-only': hideText }" key="lang_copy_to_clipboard" v-translate>Copy to Clipboard</span>
    </button>
</template>

<script>
import '~/vendor/clipboard.js';
import Icon from './Icon';

export default {
    components: { Icon },
    props: {
        text: {
            type: String,
            required: true,
        },
        hideText: {
            type: Boolean,
            default: false
        }
    },
    methods: {
        doCopy() {
            this.$copyText(this.text).then(function (e) {
            }, function (e) {
                console.error(e);
            })
        }
    }
};
</script>
