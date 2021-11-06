<template>
    <b-modal id="logs_modal" ref="modal" :title="langLogView">
        <textarea class="form-control log-viewer" spellcheck="false" readonly>{{ logs }}</textarea>

        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" class="btn_copy" @click.prevent="doCopy" type="button">
                <translate key="lang_btn_copy">Copy to Clipboard</translate>
            </b-button>
        </template>
    </b-modal>
</template>

<script>
import '~/vendor/clipboard.js';

export default {
    name: 'QueueLogsModal',
    data () {
        return {
            logs: 'Loading...',
        };
    },
    computed: {
        langLogView () {
            return this.$gettext('Log Viewer');
        }
    },
    methods: {
        show(logs) {
            let logDisplay = [];
            logs.forEach(function (log) {
                logDisplay.push(log.formatted);
            });

            this.logs = logDisplay.join('');
            this.$refs.modal.show();
        },
        doCopy() {
            this.$copyText(this.logs);
        },
        close() {
            this.$refs.modal.hide();
        }
    }
};
</script>
