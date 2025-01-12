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
                <running-badge :running="backendRunning" />
                <br>
                <small>{{ backendName }}</small>
            </h3>
        </template>

        <div class="card-body">
            <p class="card-text">
                {{ langTotalTracks }}
            </p>

            <div
                v-if="userAllowedForStation(StationPermission.Media)"
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
            v-if="userAllowedForStation(StationPermission.Broadcasting) && hasStarted"
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
                v-if="!backendRunning"
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
                v-if="backendRunning"
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

<script lang="ts">
export interface ProfileBackendPanelParentProps {
    numSongs: number,
    numPlaylists: number,
    backendType: string,
    hasStarted: boolean,
    backendRestartUri: string,
    backendStartUri: string,
    backendStopUri: string,
}
</script>

<script setup lang="ts">
import {BackendAdapter} from '~/entities/RadioAdapters';
import Icon from '~/components/Common/Icon.vue';
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";
import CardPage from "~/components/Common/CardPage.vue";
import {StationPermission, userAllowedForStation} from "~/acl";
import {IconPlay, IconStop, IconUpdate} from "~/components/Common/icons";
import useMakeApiCall from "~/components/Stations/Profile/useMakeApiCall.ts";

defineOptions({
    inheritAttrs: false
});

interface ProfileBackendPanelProps extends ProfileBackendPanelParentProps {
    backendRunning: boolean,
}

const props = defineProps<ProfileBackendPanelProps>();

const {$gettext, $ngettext} = useTranslate();

const langTotalTracks = computed(() => {
    const numSongs = $ngettext(
        '%{numSongs} uploaded song',
        '%{numSongs} uploaded songs',
        props.numSongs,
        {
            numSongs: String(props.numSongs)
        }
    );

    const numPlaylists = $ngettext(
        '%{numPlaylists} playlist',
        '%{numPlaylists} playlists',
        props.numPlaylists,
        {
            numPlaylists: String(props.numPlaylists)
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
    if (props.backendType === BackendAdapter.Liquidsoap) {
        return 'Liquidsoap';
    }
    return '';
});

const doRestart = useMakeApiCall(
    props.backendRestartUri,
    {
        title: $gettext('Restart service?'),
        confirmButtonText: $gettext('Restart')
    }
);

const doStart = useMakeApiCall(
    props.backendStartUri,
    {
        title: $gettext('Start service?'),
        confirmButtonText: $gettext('Start'),
        confirmButtonClass: 'btn-success'
    }
);

const doStop = useMakeApiCall(
    props.backendStopUri,
    {
        title: $gettext('Stop service?'),
        confirmButtonText: $gettext('Stop'),
        confirmButtonClass: 'btn-danger'
    }
);
</script>
