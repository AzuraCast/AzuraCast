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
            v-if="userAllowedForStation(StationPermission.Streamers) || userAllowedForStation(StationPermission.Profile)"
            #footer_actions
        >
            <template v-if="enableStreamers">
                <router-link
                    v-if="userAllowedForStation(StationPermission.Streamers)"
                    class="btn btn-link text-primary"
                    :to="{name: 'stations:streamers:index'}"
                >
                    <icon :icon="IconSettings" />
                    <span>
                        {{ $gettext('Manage') }}
                    </span>
                </router-link>
                <button
                    v-if="userAllowedForStation(StationPermission.Profile)"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="toggleStreamers"
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
                    @click="toggleStreamers"
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
import Icon from "~/components/Common/Icon.vue";
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import streamersPanelProps from "~/components/Stations/Profile/streamersPanelProps";
import CardPage from "~/components/Common/CardPage.vue";
import {StationPermission, userAllowedForStation} from "~/acl";
import useToggleFeature from "~/components/Stations/Profile/useToggleFeature";
import {IconCheck, IconClose, IconSettings} from "~/components/Common/icons";

const props = defineProps({
    ...streamersPanelProps
});

const toggleStreamers = useToggleFeature('enable_streamers', !props.enableStreamers);
</script>
