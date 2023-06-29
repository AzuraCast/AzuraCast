<template>
    <section
        id="profile-backend"
        class="card"
        role="region"
        aria-labelledby="hdr_backend"
    >
        <div class="card-header text-bg-primary">
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
                    class="btn btn-link"
                    :href="manageMediaUri"
                >{{ $gettext('Music Files') }}</a>
                <a
                    class="btn btn-link"
                    :href="managePlaylistsUri"
                >{{ $gettext('Playlists') }}</a>
            </div>
        </div>
        <div
            v-if="userCanManageBroadcasting && hasStarted"
            class="card-body buttons"
        >
            <a
                class="api-call no-reload btn btn-secondary"
                :href="backendRestartUri"
            >
                <icon icon="update" />
                <span>
                    {{ $gettext('Restart') }}
                </span>
            </a>
            <a
                v-show="!backendRunning"
                class="api-call no-reload btn btn-success"
                :href="backendStartUri"
            >
                <icon icon="play_arrow" />
                <span>
                    {{ $gettext('Start') }}
                </span>
            </a>
            <a
                v-show="backendRunning"
                class="api-call no-reload btn btn-danger"
                :href="backendStopUri"
            >
                <icon icon="stop" />
                <span>
                    {{ $gettext('Stop') }}
                </span>
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
