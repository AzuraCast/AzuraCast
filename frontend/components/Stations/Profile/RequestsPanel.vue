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
            v-if="userAllowedForStation(StationPermission.Broadcasting) || userAllowedForStation(StationPermission.Profile)"
            #footer_actions
        >
            <template v-if="enableRequests">
                <router-link
                    v-if="userAllowedForStation(StationPermission.Broadcasting)"
                    class="btn btn-link text-primary"
                    :to="{name: 'stations:reports:requests'}"
                >
                    <icon :icon="IconLogs" />
                    <span>
                        {{ $gettext('View') }}
                    </span>
                </router-link>
                <button
                    v-if="userAllowedForStation(StationPermission.Profile)"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="toggleRequests"
                >
                    <icon :icon="IconClose" />
                    <span>
                        {{ $gettext('Disable') }}
                    </span>
                </button>
            </template>
            <template v-else>
                <button
                    v-if="userAllowedForStation(StationPermission.Profile)"
                    type="button"
                    class="btn btn-link text-success"
                    @click="toggleRequests"
                >
                    <icon :icon="IconCheck" />
                    <span>
                        {{ $gettext('Enable') }}
                    </span>
                </button>
            </template>
        </template>
    </card-page>
</template>

<script setup lang="ts">
import Icon from '~/components/Common/Icon.vue';
import requestsPanelProps from "~/components/Stations/Profile/requestsPanelProps";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {StationPermission, userAllowedForStation} from "~/acl";
import useToggleFeature from "~/components/Stations/Profile/useToggleFeature";
import {IconCheck, IconClose, IconLogs} from "~/components/Common/icons";

const props = defineProps({
    ...requestsPanelProps
});

const toggleRequests = useToggleFeature('enable_requests', !props.enableRequests);
</script>
