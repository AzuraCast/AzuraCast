<template>
    <card-page header-id="hdr_song_requests">
        <template #header="{id}">
            <div class="d-flex align-items-center">
                <h3
                    :id="id"
                    class="card-title flex-fill my-0"
                >
                    {{ $gettext('Song Requests') }}
                </h3>
                <div class="flex-shrink-0">
                    <enabled-badge :enabled="stationData.enableRequests"/>
                </div>
            </div>
        </template>

        <template
            v-if="userAllowedForStation(StationPermissions.Broadcasting) || userAllowedForStation(StationPermissions.Profile)"
            #footer_actions
        >
            <template v-if="stationData.enableRequests">
                <router-link
                    v-if="userAllowedForStation(StationPermissions.Broadcasting)"
                    class="btn btn-link text-primary"
                    :to="{name: 'stations:reports:requests'}"
                >
                    <icon-ic-assignment/>
                    <span>
                        {{ $gettext('View') }}
                    </span>
                </router-link>
                <button
                    v-if="userAllowedForStation(StationPermissions.Profile)"
                    type="button"
                    class="btn btn-link text-danger"
                    @click="toggleRequests"
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
                    @click="toggleRequests"
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
import IconIcAssignment from "~icons/ic/baseline-assignment";

const stationData = useStationData();

const {userAllowedForStation} = useUserAllowedForStation();

const toggleRequests = useToggleFeature(
    'enable_requests',
    computed(() => stationData.value.enableRequests)
);
</script>
