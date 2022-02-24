<template>
    <b-modal size="lg" centered id="cpu_stats_help_modal" ref="modal" :title="langTitle">
        <b-container fluid>
            <b-row class="mb-1">
                <b-col>
                    <h6><b-badge pill variant="warning">&nbsp;&nbsp;</b-badge>&nbsp;Cached</h6>
                    <p class="lead">The amount of memory that Linux is using for disk caching<p/>
                    <p>This can make it look like your memory is low while it actually is not. Some monitoring solutions/panels include cached memory in their used memory statistics without indicating this.</p>
                    <p>Disk caching makes a system much faster and more responsive in general. It does not take memory away from applications in any way since it will automatically be released by the operating system when needed.</p>
                </b-col>
            </b-row>
            <b-row class="mb-1">
                <b-col>
                    <h6><b-badge pill variant="primary">&nbsp;&nbsp;</b-badge>&nbsp;Used</h6>
                    <p class="lead">The current Memory usage excluding cached memory.<p/>
                </b-col>
            </b-row>
        </b-container>

        <template #modal-footer>
            <slot name="modal-footer">
                <b-button variant="default" type="button" @click="close">
                    <translate key="lang_btn_close">Close</translate>
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script>

export default {
    name: 'MemoryStatsHelpModal',
    computed: {
        langTitle() {
            return this.$gettext('Memory Stats Help');
        }
    },
    methods: {
        create() {
            this.$refs.modal.show();
        },
        close() {
            this.$refs.modal.hide();
        }
    }
};
</script>
