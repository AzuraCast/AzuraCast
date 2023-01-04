<template>
    <section
        class="card mb-4"
        role="region"
    >
        <template v-if="enableRequests">
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Song Requests') }}
                    <enabled-badge :enabled="true" />
                </h3>
            </div>
            <div
                v-if="userCanManageReports || userCanManageProfile"
                class="card-actions"
            >
                <a
                    v-if="userCanManageReports"
                    class="btn btn-outline-primary"
                    :href="requestsViewUri"
                >
                    <icon icon="assignment" />
                    {{ $gettext('View') }}
                </a>
                <a
                    v-if="userCanManageProfile"
                    class="btn btn-outline-danger"
                    :data-confirm-title="$gettext('Disable song requests?')"
                    :href="requestsToggleUri"
                >
                    <icon icon="close" />
                    {{ $gettext('Disable') }}
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header bg-primary-dark">
                <h3 class="card-title">
                    {{ $gettext('Song Requests') }}
                    <enabled-badge :enabled="false" />
                </h3>
            </div>
            <div
                v-if="userCanManageProfile"
                class="card-actions"
            >
                <a
                    class="btn btn-outline-success"
                    :data-confirm-title="$gettext('Enable song requests?')"
                    :href="requestsToggleUri"
                >
                    <icon icon="check" />
                    {{ $gettext('Enable') }}
                </a>
            </div>
        </template>
    </section>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import requestsPanelProps from "~/components/Stations/Profile/requestsPanelProps";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";

const props = defineProps({
    ...requestsPanelProps
});
</script>
