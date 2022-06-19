<template>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">
                        <translate key="hdr_logs">Available Logs</translate>
                    </h2>
                </div>

                <log-list :url="logsUrl" @view="viewLog"></log-list>
            </div>

            <streaming-log-modal ref="modal"></streaming-log-modal>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary-dark">
                    <h2 class="card-title">
                        <translate key="hdr_need_help">Need Help?</translate>
                    </h2>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <translate key="help_section_1">You can find answers for many common questions in our support documents.</translate>
                    </p>
                    <p class="card-text">
                        <a href="https://docs.azuracast.com/en/user-guide/troubleshooting" target="_blank">
                            <translate key="help_link_support_docs">Support Documents</translate>
                        </a>
                    </p>
                    <p class="card-text">
                        <translate key="help_section_2">If you're experiencing a bug or error, you can submit a GitHub issue using the link below.</translate>
                    </p>
                </div>
                <div class="card-actions">
                    <a class="btn btn-outline-primary" role="button"
                       href="https://github.com/AzuraCast/AzuraCast/issues/new/choose" target="_blank">
                        <icon icon="contact_support"></icon>
                        <translate key="btn_add_github_issue">Add New GitHub Issue</translate>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Icon from "~/components/Common/Icon";
import StreamingLogModal from "~/components/Common/StreamingLogModal";
import LogList from "~/components/Common/LogList";

export default {
    name: 'StationsHelp',
    components: {LogList, StreamingLogModal, Icon},
    props: {
        logsUrl: String,
    },
    methods: {
        viewLog(url) {
            this.$refs.modal.show(url);
        }
    }
}
</script>
