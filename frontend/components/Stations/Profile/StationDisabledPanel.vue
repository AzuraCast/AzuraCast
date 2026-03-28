<template>
    <div class="outside-card-header d-flex align-items-center">
        <div class="flex-fill">
            <h2 class="display-6 m-0">
                {{ stationData.name }}
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
                <icon-ic-edit/>

                <span>
                    {{ $gettext('Edit Profile') }}
                </span>
            </router-link>
        </div>
    </div>

    <card-page
        id="station-disabled"
        :title="$gettext('Station Disabled')"
    >
        <div class="card-body">
            <p class="card-text">
                {{ $gettext('Your station is currently not enabled for broadcasting. You can still manage media, playlists, and other station settings. To re-enable broadcasting, edit your station profile.') }}
            </p>
        </div>
    </card-page>
</template>
<script setup lang="ts">
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";
import CardPage from "~/components/Common/CardPage.vue";
import {StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import IconIcEdit from "~icons/ic/baseline-edit";

const stationData = useStationData();

const {userAllowedForStation} = useUserAllowedForStation();
</script>
