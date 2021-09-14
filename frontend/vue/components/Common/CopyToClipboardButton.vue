<template>
    <button ref="btn" class="btn btn-copy btn-link btn-xs" :data-clipboard-target="target" v-bind="$attrs">
        <icon class="sm" icon="file_copy"></icon>
        <span :class="{ 'sr-only': hideText }" key="lang_copy_to_clipboard" v-translate>Copy to Clipboard</span>
    </button>
</template>

<script>
import Clipboard from 'clipboard/dist/clipboard.min.js';
import Icon from './Icon';

export default {
    components: { Icon },
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
