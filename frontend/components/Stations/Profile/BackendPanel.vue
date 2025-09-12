<template>
    <card-page
        id="profile-backend"
        header-id="hdr_backend"
    >
        <template #header="{id}">
            <h3
                :id="id"
                class="card-title"
            >
                {{ $gettext('AutoDJ Service') }}
                <running-badge :running="profileData.services.backendRunning"/>
                <br>
                <small>{{ backendName }}</small>
            </h3>
        </template>

        <div class="card-body">
            <p class="card-text">
                {{ langTotalTracks }}
            </p>

            <div
                v-if="userAllowedForStation(StationPermissions.Media)"
                class="buttons"
            >
                <router-link
                    class="btn btn-primary"
                    :to="{name: 'stations:files:index'}"
                >
                    {{ $gettext('Music Files') }}
                </router-link>
                <router-link
                    class="btn btn-primary"
                    :to="{name: 'stations:playlists:index'}"
                >
                    {{ $gettext('Playlists') }}
                </router-link>
            </div>
        </div>

        <template
            v-if="userAllowedForStation(StationPermissions.Broadcasting) && stationData.hasStarted"
            #footer_actions
        >
            <button
                type="button"
                class="btn btn-link text-secondary"
                @click="doRestart()"
            >
                <icon :icon="IconUpdate" />
                <span>
                    {{ $gettext('Restart') }}
                </span>
            </button>
            <button
                v-if="!profileData.services.backendRunning"
                type="button"
                class="btn btn-link text-success"
                @click="doStart()"
            >
                <icon :icon="IconPlay" />
                <span>
                    {{ $gettext('Start') }}
                </span>
            </button>
            <button
                v-if="profileData.services.backendRunning"
                type="button"
                class="btn btn-link text-danger"
                @click="doStop()"
            >
                <icon :icon="IconStop" />
                <span>
                    {{ $gettext('Stop') }}
                </span>
            </button>
        </template>
    </card-page>
</template>

<script setup lang="ts">
import Icon from "~/components/Common/Icon.vue";
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";
import CardPage from "~/components/Common/CardPage.vue";
import {userAllowedForStation} from "~/acl";
import {IconPlay, IconStop, IconUpdate} from "~/components/Common/icons";
import useMakeApiCall from "~/components/Stations/Profile/useMakeApiCall.ts";
import {BackendAdapters, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {getStationApiUrl} from "~/router.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useStationProfileData} from "~/components/Stations/Profile/useProfileQuery.ts";

const stationData = useStationData();
const profileData = useStationProfileData();

const backendRestartUri = getStationApiUrl('/backend/restart');
const backendStartUri = getStationApiUrl('/backend/start');
const backendStopUri = getStationApiUrl('/backend/stop');

const {$gettext, $ngettext} = useTranslate();

const langTotalTracks = computed(() => {
    const numSongs = $ngettext(
        '%{numSongs} uploaded song',
        '%{numSongs} uploaded songs',
        profileData.value.numSongs,
        {
            numSongs: String(profileData.value.numSongs)
        }
    );

    const numPlaylists = $ngettext(
        '%{numPlaylists} playlist',
        '%{numPlaylists} playlists',
        profileData.value.numPlaylists,
        {
            numPlaylists: String(profileData.value.numPlaylists)
        }
    );

    return $gettext(
        'LiquidSoap is currently shuffling from %{songs} and %{playlists}.',
        {
            songs: numSongs,
            playlists: numPlaylists
        }
    );
});

const backendName = computed(() => {
    if (stationData.value.backendType === BackendAdapters.Liquidsoap) {
        return 'Liquidsoap';
    }
    return '';
});

const doRestart = useMakeApiCall(
    backendRestartUri,
    {
        title: $gettext('Restart service?'),
        confirmButtonText: $gettext('Restart')
    }
);

const doStart = useMakeApiCall(
    backendStartUri,
    {
        title: $gettext('Start service?'),
        confirmButtonText: $gettext('Start'),
        confirmButtonClass: 'btn-success'
    }
);

const doStop = useMakeApiCall(
    backendStopUri,
    {
        title: $gettext('Stop service?'),
        confirmButtonText: $gettext('Stop'),
        confirmButtonClass: 'btn-danger'
    }
);
</script>
