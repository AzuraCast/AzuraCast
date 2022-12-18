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
                <a class="btn btn-outline-danger" v-if="userCanManageProfile"
                   :data-confirm-title="$gettext('Disable song requests?')" :href="requestsToggleUri">
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
                <a class="btn btn-outline-success" :data-confirm-title="$gettext('Enable song requests?')"
                   :href="requestsToggleUri">
                    <icon icon="check"></icon>
                    {{ $gettext('Enable') }}
                </a>
            </div>
        </template>
    </section>
</template>

<script>
export const profileRequestsProps = {
    enableRequests: Boolean,
    userCanManageReports: Boolean,
    userCanManageProfile: Boolean,
    requestsViewUri: String,
    requestsToggleUri: String
};

export default {
    inheritAttrs: false
};
</script>

<script setup>
import Icon from '~/components/Common/Icon';

const props = defineProps({
    ...profileRequestsProps
});
</script>
