<template>
    <section
        class="card"
        role="region"
        aria-labelledby="hdr_song_requests"
    >
        <template v-if="enableRequests">
            <div class="card-header text-bg-primary">
                <h3
                    id="hdr_song_requests"
                    class="card-title"
                >
                    {{ $gettext('Song Requests') }}
                    <enabled-badge :enabled="true" />
                </h3>
            </div>
            <div
                v-if="userCanManageReports || userCanManageProfile"
                class="card-body buttons"
            >
                <a
                    v-if="userCanManageReports"
                    class="btn btn-primary"
                    :href="requestsViewUri"
                >
                    <icon icon="assignment" />
                    <span>
                        {{ $gettext('View') }}
                    </span>
                </a>
                <a
                    v-if="userCanManageProfile"
                    class="btn btn-danger"
                    :data-confirm-title="$gettext('Disable song requests?')"
                    :href="requestsToggleUri"
                >
                    <icon icon="close" />
                    <span>
                        {{ $gettext('Disable') }}
                    </span>
                </a>
            </div>
        </template>
        <template v-else>
            <div class="card-header text-bg-primary">
                <h3 class="card-title">
                    {{ $gettext('Song Requests') }}
                    <enabled-badge :enabled="false" />
                </h3>
            </div>
            <div
                v-if="userCanManageProfile"
                class="card-body buttons"
            >
                <a
                    class="btn btn-success"
                    :data-confirm-title="$gettext('Enable song requests?')"
                    :href="requestsToggleUri"
                >
                    <icon icon="check" />
                    <span>
                        {{ $gettext('Enable') }}
                    </span>
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
