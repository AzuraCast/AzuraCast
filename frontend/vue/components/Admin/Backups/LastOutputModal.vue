<template>
    <b-modal size="md" id="log_view_modal" ref="modal" :title="langTitle">
        <pre id="modal-log-view-contents" class="form-control log-viewer"
             style="height: 300px; overflow-y: auto;">{{ lastOutput }}</pre>
    </b-modal>
</template>

<script>
export default {
    name: 'AdminBackupsLastOutputModal',
    props: {
        lastOutput: String,
    },
    computed: {
        langTitle() {
            return this.$gettext('Log Viewer');
        }
    },
    methods: {
        show() {
            this.$refs.modal.show();
        }
    }
}
</script>
