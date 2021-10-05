<template>
    <b-modal id="logs_modal" ref="modal" :title="langLogView">
        <streaming-log-view ref="logView" :log-url="logUrl"></streaming-log-view>

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
import StreamingLogView from "~/components/Common/StreamingLogView";

export default {
    name: 'StreamingLogModal',
    components: {StreamingLogView},
    data() {
        return {
            logUrl: null,
        };
    },
    computed: {
        langLogView() {
            return this.$gettext('Log View');
        }
    },
    methods: {
        show(logUrl) {
            this.logUrl = logUrl;
            this.$refs.modal.show();
        },
        doCopy() {
            this.$copyText(this.$refs.logView.getContents());
        },
        close() {
            this.logUrl = null;
            this.log = null;
            this.$refs.modal.hide();
        }
    }
};
</script>
