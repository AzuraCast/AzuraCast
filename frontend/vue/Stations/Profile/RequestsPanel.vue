<template>
    <section class="card mb-4" role="region">
        <template v-if="enableRequests">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    <translate key="lang_profile_requests_title">Song Requests</translate>
                    <small class="badge badge-pill badge-success" key="lang_profile_requests_enabled" v-translate>Enabled</small>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageReports || userCanManageProfile">
                <a class="btn btn-outline-primary" v-if="userCanManageReports" :href="requestsViewUri">
                    <icon icon="assignment"></icon>
                    <translate key="lang_profile_requests_view">View</translate>
                </a>
                <a class="btn btn-outline-danger" v-if="userCanManageProfile" :data-confirm-title="langDisableRequests" :href="requestsToggleUri">
                    <icon icon="close"></icon>
                    <translate key="lang_profile_requests_disable">Disable</translate>
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    <translate key="lang_profile_requests_title">Song Requests</translate>
                    <small class="badge badge-pill badge-danger" key="lang_profile_requests_disabled" v-translate>Disabled</small>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-success" :data-confirm-title="langEnableRequests" :href="requestsToggleUri">
                    <icon icon="check"></icon>
                    <translate key="lang_profile_requests_enable">Enable</translate>
                </a>
            </div>
        </template>
    </section>
</template>

<script>
import Icon from '../../Common/Icon';

export const profileRequestsProps = {
    props: {
        enableRequests: Boolean,
        userCanManageReports: Boolean,
        userCanManageProfile: Boolean,
        requestsViewUri: String,
        requestsToggleUri: String
    }
};

export default {
    components: { Icon },
    mixins: [profileRequestsProps],
    computed: {
        langDisableRequests () {
            return this.$gettext('Disable song requests?');
        },
        langEnableRequests () {
            return this.$gettext('Enable song requests?');
        }
    }
};
</script>
