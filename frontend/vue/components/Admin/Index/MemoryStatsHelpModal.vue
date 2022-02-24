<template>
    <b-modal size="lg" centered id="cpu_stats_help_modal" ref="modal" :title="langTitle">
        <div class="mb-2">
            <h6>
                <b-badge pill variant="danger">&nbsp;&nbsp;</b-badge>&nbsp;
                <translate key="lang_cached">Cached</translate>
                :
                <translate
                    key="lang_cached_1">The amount of memory Linux is using for disk caching.</translate>
            </h6>
            <div class="ml-4">
                <p>
                    <translate key="lang_cached_2">This can make it look like your memory is low while it actually is not. Some monitoring solutions/panels include cached memory in their used memory statistics without indicating this.</translate>
                </p>
                <p>
                    <translate key="lang_cached_3">Disk caching makes a system much faster and more responsive in general. It does not take memory away from applications in any way since it will automatically be released by the operating system when needed.</translate>
                </p>
            </div>
        </div>

        <div class="mb-2">
            <h6>
                <b-badge pill variant="primary">&nbsp;&nbsp;</b-badge>&nbsp;
                <translate key="lang_used">Used</translate>
                :
                <translate key="lang_used_1">The current Memory usage excluding cached memory.</translate>
            </h6>
        </div>

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
