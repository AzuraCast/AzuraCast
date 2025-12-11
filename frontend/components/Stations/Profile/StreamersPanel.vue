<template>
    <card-page header-id="hdr_streamers">
        <template #header="{id}">
            <div class="d-flex align-items-center">
                <h3
                    :id="id"
                    class="card-title flex-fill my-0"
                >
                    {{ $gettext('Streamers/DJs') }}
                </h3>
                <div class="flex-shrink-0">
                    <enabled-badge :enabled="stationData.enableStreamers"/>
                </div>
            </div>
        </template>
        <template
            v-if="(stationData.enableStreamers && (stationData.enablePublicPages || userAllowedForStation(StationPermissions.Streamers))) || userAllowedForStation(StationPermissions.Profile)"
            #footer_actions
        >
            <template v-if="stationData.enableStreamers">
                <a
                    v-if="stationData.enablePublicPages"
                    :href="stationData.webDjUrl"
                    target="_blank"
                    class="btn btn-link text-secondary"
                >
                    <icon-ic-mic/>

                    <span>
                        {{ $gettext('Web DJ') }}
                    </span>
                </a>
                <router-link
                    v-if="userAllowedForStation(StationPermissions.Streamers)"
                    class="btn btn-link text-primary"
                    :to="{name: 'stations:streamers:index'}"
                >
                    <icon-ic-settings/>

                    <span>
                        {{ $gettext('Manage') }}
                    </span>
                </router-link>
                <button
                    v-if="userAllowedForStation(StationPermissions.Profile)"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="toggleStreamers"
                >
                    <icon-ic-close/>

                    <span>
                        {{ $gettext('Disable') }}
                    </span>
                </button>
            </template>
            <template v-else>
                <button
                    v-if="userAllowedForStation(StationPermissions.Profile)"
                    type="button"
                    class="btn btn-link text-success"
                    @click="toggleStreamers"
                >
                    <icon-ic-check/>

                    <span>
                        {{ $gettext('Enable') }}
                    </span>
                </button>
            </template>
        </template>
    </card-page>
</template>

<script setup lang="ts">
import EnabledBadge from "~/components/Common/Badges/EnabledBadge.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";
import useToggleFeature from "~/components/Stations/Profile/useToggleFeature";
import {computed} from "vue";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import IconIcCheck from "~icons/ic/baseline-check";
import IconIcClose from "~icons/ic/baseline-close";
import IconIcMic from "~icons/ic/baseline-mic";
import IconIcSettings from "~icons/ic/baseline-settings";

const stationData = useStationData();

const {userAllowedForStation} = useUserAllowedForStation();

const toggleStreamers = useToggleFeature(
    'enable_streamers',
    computed(() => stationData.value.enableStreamers)
);
</script>
