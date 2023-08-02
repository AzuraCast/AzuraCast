<template>
    <card-page header-id="hdr_streamers">
        <template #header="{id}">
            <h3
                :id="id"
                class="card-title"
            >
                {{ $gettext('Streamers/DJs') }}
                <enabled-badge :enabled="enableStreamers" />
            </h3>
        </template>
        <template
            v-if="userCanManageStreamers || userCanManageProfile"
            #footer_actions
        >
            <template v-if="enableStreamers">
                <a
                    v-if="userCanManageStreamers"
                    class="btn btn-link text-primary"
                    :href="streamersViewUri"
                >
                    <icon icon="settings" />
                    <span>
                        {{ $gettext('Manage') }}
                    </span>
                </a>
                <a
                    v-if="userCanManageProfile"
                    v-confirm-link="$gettext('Disable streamers?')"
                    class="btn btn-link text-danger"
                    :href="streamersToggleUri"
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
                    v-confirm-link="$gettext('Enable streamers?')"
                    class="btn btn-link text-success"
                    :href="streamersToggleUri"
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
import Icon from "~/components/Common/Icon.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import streamersPanelProps from "~/components/Stations/Profile/streamersPanelProps";
import CardPage from "~/components/Common/CardPage.vue";
import {useSweetAlert} from "~/vendor/sweetalert";

const props = defineProps({
    ...streamersPanelProps
});

const {vConfirmLink} = useSweetAlert();
</script>
