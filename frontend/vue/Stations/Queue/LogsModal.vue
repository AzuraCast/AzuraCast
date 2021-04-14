<template>
    <b-modal id="logs_modal" ref="modal" :title="langLogView">
        <textarea class="form-control log-viewer" id="log-view-contents" spellcheck="false" readonly>{{ logs }}</textarea>

        <template v-slot:modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" class="btn_copy" data-clipboard-target="#log-view-contents" type="button">
                <translate key="lang_btn_copy">Copy to Clipboard</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import Clipboard from 'clipboard/dist/clipboard.min.js';

export default {
    name: 'QueueLogsModal',
    data () {
        return {
            logs: 'Loading...',
            clipboard: null
        };
    },
    computed: {
        langLogView () {
            return this.$gettext('Log View');
        }
    },
    mounted () {
        this.clipboard = new Clipboard('.btn_copy');
    },
    beforeDestroy () {
        this.clipboard.destroy();
    },
    methods: {
        show (logs) {
            let logDisplay = [];
            logs.forEach(function (log) {
                logDisplay.push(log.formatted);
            });

            this.logs = logDisplay.join('');
            this.$refs.modal.show();
        },
        close () {
            this.$refs.modal.hide();
        }
    }
};
</script>
