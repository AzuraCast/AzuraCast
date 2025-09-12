<template>
    <div class="outside-card-header d-flex align-items-center">
        <div
            v-if="profileData.station.listen_url && stationData.hasStarted"
            class="flex-shrink-0 me-2"
        >
            <play-button
                class="btn-xl"
                :stream="{
                    url: profileData.station.listen_url,
                    title: stationData.name,
                    isStream: true
                }"
            />
        </div>
        <div class="flex-fill">
            <h2 class="display-6 m-0">
                {{ stationData.name }}<br>
                <small
                    v-if="stationData.description"
                    class="text-muted"
                >
                    {{ stationData.description }}
                </small>
            </h2>
        </div>
        <div
            v-if="userAllowedForStation(StationPermissions.Profile)"
            class="flex-shrink-0 ms-3"
        >
            <router-link
                class="btn btn-primary"
                role="button"
                :to="{name: 'stations:profile:edit'}"
            >
                <icon :icon="IconEdit" />
                <span>
                    {{ $gettext('Edit Profile') }}
                </span>
            </router-link>
        </div>
    </div>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icons/Icon.vue";
import PlayButton from "~/components/Common/Audio/PlayButton.vue";
import {userAllowedForStation} from "~/acl";
import {IconEdit} from "~/components/Common/Icons/icons.ts";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useStationProfileData} from "~/components/Stations/Profile/useProfileQuery.ts";

const stationData = useStationData();
const profileData = useStationProfileData();
</script>
