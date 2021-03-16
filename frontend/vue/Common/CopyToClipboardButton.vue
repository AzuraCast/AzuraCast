<template>
    <button ref="btn" class="btn btn-copy btn-link btn-xs" :data-clipboard-target="target" v-bind="$attrs">
        <i class="material-icons sm">file_copy</i>
        <span :class="{ 'sr-only': hideText }" key="lang_copy_to_clipboard" v-translate>Copy to Clipboard</span>
    </button>
</template>

<script>
import Clipboard from 'clipboard/dist/clipboard.min.js';

export default {
    props: {
        target: {
            type: String,
            required: true
        },
        hideText: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            clipboard: null
        };
    },
    mounted () {
        this.clipboard = new Clipboard(this.$refs.btn);
    },
    beforeDestroy () {
        this.clipboard.destroy();
    }
};
</script>
