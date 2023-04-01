<template>
    <section
        id="profile-backend"
        class="card"
        role="region"
        aria-labelledby="hdr_backend"
    >
        <div class="card-header bg-primary-dark">
            <h3
                id="hdr_backend"
                class="card-title"
            >
                {{ $gettext('AutoDJ Service') }}
                <running-badge :running="backendRunning" />
                <br>
                <small>{{ backendName }}</small>
            </h3>
        </div>
        <div class="card-body">
            <p class="card-text">
                {{ langTotalTracks }}
            </p>

            <div
                v-if="userCanManageMedia"
                class="buttons"
            >
                <a
                    class="btn btn-primary"
                    :href="manageMediaUri"
                >{{ $gettext('Music Files') }}</a>
                <a
                    class="btn btn-primary"
                    :href="managePlaylistsUri"
                >{{ $gettext('Playlists') }}</a>
            </div>
        </div>
        <div
            v-if="userCanManageBroadcasting && hasStarted"
            class="card-actions"
        >
            <a
                class="api-call no-reload btn btn-outline-secondary"
                :href="backendRestartUri"
            >
                <icon icon="update" />
                {{ $gettext('Restart') }}
            </a>
            <a
                v-show="!backendRunning"
                class="api-call no-reload btn btn-outline-success"
                :href="backendStartUri"
            >
                <icon icon="play_arrow" />
                {{ $gettext('Start') }}
            </a>
            <a
                v-show="backendRunning"
                class="api-call no-reload btn btn-outline-danger"
                :href="backendStopUri"
            >
                <icon icon="stop" />
                {{ $gettext('Stop') }}
            </a>
        </div>
    </section>
</template>

<script setup>
import {BACKEND_LIQUIDSOAP} from '~/components/Entity/RadioAdapters';
import Icon from '~/components/Common/Icon';
import RunningBadge from "~/components/Common/Badges/RunningBadge.vue";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";
import backendPanelProps from "~/components/Stations/Profile/backendPanelProps";

const props = defineProps({
    ...backendPanelProps,
    backendRunning: {
        type: Boolean,
        required: true
    }
});

const {$gettext, $ngettext} = useTranslate();

const langTotalTracks = computed(() => {
    let numSongs = $ngettext(
        '%{numSongs} uploaded song',
        '%{numSongs} uploaded songs',
        props.numSongs,
        {numSongs: props.numSongs}
    );

    let numPlaylists = $ngettext(
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
    if (props.backendType === BACKEND_LIQUIDSOAP) {
        return 'Liquidsoap';
    }
    return '';
});

</script>
