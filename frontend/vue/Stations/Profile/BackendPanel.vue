<template>
    <section class="card" role="region" id="profile-backend">
        <div class="card-header bg-primary-dark">
            <h3 class="card-title">
                <translate key="lang_profile_backend_title">AutoDJ Service</translate>

                <small class="badge badge-pill badge-success" v-if="np.services.backend_running" key="lang_profile_backend_running" v-translate>Running</small>
                <small class="badge badge-pill badge-danger" v-else key="lang_profile_backend_not_running" v-translate>Not Running</small>
                <br>
                <small>{{ backendName }}</small>
            </h3>
        </div>
        <div class="card-body">
            <p class="card-text">
                {{ langTotalTracks }}
            </p>

            <div class="buttons" v-if="userCanManageMedia">
                <a class="btn btn-primary" :href="manageMediaUri" key="lang_profile_manage_media">Music Files</a>
                <a class="btn btn-primary" :href="managePlaylistsUri" key="lang_profile_manage_playlists" v-translate>Playlists</a>
            </div>
        </div>
        <div class="card-actions" v-if="userCanManageBroadcasting">
            <a class="api-call no-reload btn btn-outline-secondary" :href="backendRestartUri">
                <icon icon="update"></icon>
                <translate key="lang_profile_backend_restart">Restart</translate>
            </a>
            <a class="api-call no-reload btn btn-outline-success" v-show="!np.services.backend_running" :href="backendStartUri">
                <icon icon="play_arrow"></icon>
                <translate key="lang_profile_backend_start">Start</translate>
            </a>
            <a class="api-call no-reload btn btn-outline-danger" v-show="np.services.backend_running" :href="backendStopUri">
                <icon icon="stop"></icon>
                <translate key="lang_profile_backend_stop">Stop</translate>
            </a>
        </div>
    </section>
</template>

<script>
import { BACKEND_LIQUIDSOAP } from '../../Entity/RadioAdapters.js';
import Icon from '../../Common/Icon';

export const profileBackendProps = {
    props: {
        numSongs: Number,
        numPlaylists: Number,
        backendType: String,
        userCanManageBroadcasting: Boolean,
        userCanManageMedia: Boolean,
        manageMediaUri: String,
        managePlaylistsUri: String,
        backendRestartUri: String,
        backendStartUri: String,
        backendStopUri: String
    }
};

export default {
    components: { Icon },
    mixins: [profileBackendProps],
    props: {
        np: Object
    },
    computed: {
        langTotalTracks () {
            let numSongsRaw = this.$ngettext('%{numSongs} uploaded song', '%{numSongs} uploaded songs', this.numSongs);
            let numSongs = this.$gettextInterpolate(numSongsRaw, { numSongs: this.numSongs });

            let numPlaylistsRaw = this.$ngettext('%{numPlaylists} playlist', '%{numPlaylists} playlists', this.numPlaylists);
            let numPlaylists = this.$gettextInterpolate(numPlaylistsRaw, { numPlaylists: this.numPlaylists });

            let translated = this.$gettext('LiquidSoap is currently shuffling from %{songs} and %{playlists}.');
            return this.$gettextInterpolate(translated, {
                songs: numSongs,
                playlists: numPlaylists
            });
        },
        backendName () {
            if (this.backendType === BACKEND_LIQUIDSOAP) {
                return 'Liquidsoap';
            }
            return '';
        }
    }
};
</script>
