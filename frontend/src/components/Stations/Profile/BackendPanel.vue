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
                @click="makeApiCall(backendRestartUri)"
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
                @click="makeApiCall(backendStartUri)"
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
                @click="makeApiCall(backendStopUri)"
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
import {BackendAdapter} from '~/entities/RadioAdapters';
import Icon from '~/components/Common/Icon.vue';
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";
import backendPanelProps from "~/components/Stations/Profile/backendPanelProps";
import CardPage from "~/components/Common/CardPage.vue";
import {StationPermission, userAllowedForStation} from "~/acl";
import {IconPlay, IconStop, IconUpdate} from "~/components/Common/icons";

const props = defineProps({
    ...backendPanelProps,
    backendRunning: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['api-call']);

const {$gettext, $ngettext} = useTranslate();

const langTotalTracks = computed(() => {
    const numSongs = $ngettext(
        '%{numSongs} uploaded song',
        '%{numSongs} uploaded songs',
        props.numSongs,
        {numSongs: props.numSongs}
    );

    const numPlaylists = $ngettext(
        '%{numPlaylists} playlist',
        '%{numPlaylists} playlists',
        props.numPlaylists,
        {numPlaylists: props.numPlaylists}
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

const makeApiCall = (uri) => {
    emit('api-call', uri);
};
</script>
