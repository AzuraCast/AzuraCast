<template>
    <button ref="btn" class="btn btn-copy btn-link btn-xs" :data-clipboard-target="target" v-bind="$attrs">
        <i class="material-icons sm">file_copy</i>
        <span class="sr-only" key="lang_copy_to_clipboard" v-translate>Copy to Clipboard</span>
    </button>
</template>

<script>
import Clipboard from 'clipboard/dist/clipboard.min.js';

export default {
    props: {
        target: {
            type: String,
            required: true
        }
    },
    mounted () {
        let clipboard = new Clipboard(this.$refs.btn);
        clipboard.on('success', function (e) {
            clipboard.destroy();
            resolve(e);
        });
        clipboard.on('error', function (e) {
            clipboard.destroy();
            reject(e);
        });
    }
};
</script>
