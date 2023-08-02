<template>
    <card-page header-id="hdr_song_requests">
        <template #header="{id}">
            <h3
                :id="id"
                class="card-title"
            >
                {{ $gettext('Song Requests') }}
                <enabled-badge :enabled="enableRequests" />
            </h3>
        </template>

        <template
            v-if="userCanManageReports || userCanManageProfile"
            #footer_actions
        >
            <template v-if="enableRequests">
                <a
                    v-if="userCanManageReports"
                    class="btn btn-link text-primary"
                    :href="requestsViewUri"
                >
                    <icon icon="assignment" />
                    <span>
                        {{ $gettext('View') }}
                    </span>
                </a>
                <a
                    v-if="userCanManageProfile"
                    v-confirm-link="$gettext('Disable song requests?')"
                    class="btn btn-link text-danger"
                    :href="requestsToggleUri"
                >
                    <icon icon="close" />
                    <span>
                        {{ $gettext('Disable') }}
                    </span>
                </a>
            </template>
            <template v-else>
                <a
                    v-if="userCanManageProfile"
                    v-confirm-link="$gettext('Enable song requests?')"
                    class="btn btn-link text-success"
                    :href="requestsToggleUri"
                >
                    <icon icon="check" />
                    <span>
                        {{ $gettext('Enable') }}
                    </span>
                </a>
            </template>
        </template>
    </card-page>
</template>

<script setup>
import Icon from '~/components/Common/Icon';
import requestsPanelProps from "~/components/Stations/Profile/requestsPanelProps";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {useSweetAlert} from "~/vendor/sweetalert";

const props = defineProps({
    ...requestsPanelProps
});

const {vConfirmLink} = useSweetAlert();
</script>
