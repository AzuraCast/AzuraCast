<template>
    <div>
        <div class="public-page">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">{{ stationName }}</h2>

                    <div class="stations nowplaying">
                        <radio-player v-bind="$props" @np_updated="onNowPlayingUpdate"></radio-player>
                    </div>
                </div>

                <div class="card-actions">
                    <a class="btn btn-sm btn-outline-secondary" v-b-modal.song_history_modal>
                        <icon icon="history"></icon>
                        {{ langSongHistory }}
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" v-if="enableRequests" v-b-modal.request_modal>
                        <icon icon="help_outline"></icon>
                        {{ langRequestSong }}
                    </a>
                    <a class="btn btn-sm btn-outline-secondary" :href="downloadPlaylistUri">
                        <icon icon="file_download"></icon>
                        {{ langDownloadPlaylist }}
                    </a>
                </div>
            </div>
        </div>

        <song-history-modal ref="history_modal"></song-history-modal>
        <request-modal :request-list-uri="requestListUri" :custom-fields="customFields"></request-modal>
    </div>
</template>

<script>
import RadioPlayer, { radioPlayerProps } from './Player';
import SongHistoryModal from './FullPlayer/SongHistoryModal';
import RequestModal from './FullPlayer/RequestModal';
import Icon from '../Common/Icon';

export default {
    inheritAttrs: false,
    components: { Icon, RequestModal, SongHistoryModal, RadioPlayer },
    mixins: [radioPlayerProps],
    props: {
        stationName: {
            type: String,
            required: true
        },
        enableRequests: {
            type: Boolean,
            default: false
        },
        downloadPlaylistUri: {
            type: String,
            required: true
        },
        requestListUri: {
            type: String,
            required: true
        },
        customFields: {
            type: Array,
            required: false,
            default: () => []
        }
    },
    computed: {
        langSongHistory () {
            return this.$gettext('Song History');
        },
        langRequestSong () {
            return this.$gettext('Request Song');
        },
        langDownloadPlaylist () {
            return this.$gettext('Playlist');
        }
    },
    methods: {
        onNowPlayingUpdate (newNowPlaying) {
            this.$refs.history_modal.updateHistory(newNowPlaying);
        }
    }
};
</script>
