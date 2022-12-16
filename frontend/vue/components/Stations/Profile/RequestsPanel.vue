<template>
    <section class="card mb-4" role="region">
        <template v-if="enableRequests">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Song Requests') }}
                    <enabled-badge :enabled="true"></enabled-badge>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageReports || userCanManageProfile">
                <a class="btn btn-outline-primary" v-if="userCanManageReports" :href="requestsViewUri">
                    <icon icon="assignment"></icon>
                    {{ $gettext('View') }}
                </a>
                <a class="btn btn-outline-danger" v-if="userCanManageProfile" :data-confirm-title="langDisableRequests" :href="requestsToggleUri">
                    <icon icon="close"></icon>
                    {{ $gettext('Disable') }}
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Song Requests') }}
                    <enabled-badge :enabled="false"></enabled-badge>
                </h3>
            </div>
            <div class="card-actions" v-if="userCanManageProfile">
                <a class="btn btn-outline-success" :data-confirm-title="langEnableRequests" :href="requestsToggleUri">
                    <icon icon="check"></icon>
                    {{ $gettext('Enable') }}
                </a>
            </div>
        </template>
    </section>
</template>

<script>
import Icon from '~/components/Common/Icon';
import EnabledBadge from "./Common/EnabledBadge.vue";

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
    inheritAttrs: false,
    components: {EnabledBadge, Icon},
    mixins: [profileRequestsProps],
    computed: {
        langDisableRequests() {
            return this.$gettext('Disable song requests?');
        },
        langEnableRequests() {
            return this.$gettext('Enable song requests?');
        }
    }
};
</script>
