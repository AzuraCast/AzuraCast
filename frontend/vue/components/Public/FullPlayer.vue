<template>
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
                    {{ $gettext('Song History') }}
                </a>
                <a class="btn btn-sm btn-outline-secondary" v-if="enableRequests" v-b-modal.request_modal>
                    <icon icon="help_outline"></icon>
                    {{ $gettext('Request Song') }}
                </a>
                <a class="btn btn-sm btn-outline-secondary" :href="downloadPlaylistUri">
                    <icon icon="file_download"></icon>
                    {{ $gettext('Playlist') }}
                </a>
            </div>
        </div>
    </div>

    <song-history-modal :show-album-art="showAlbumArt" :history="history"></song-history-modal>
    <request-modal :show-album-art="showAlbumArt" :request-list-uri="requestListUri"
                   :custom-fields="customFields"></request-modal>
</template>

<script setup>
import SongHistoryModal from './FullPlayer/SongHistoryModal';
import RequestModal from './FullPlayer/RequestModal';
import Icon from '~/components/Common/Icon';
import RadioPlayer from './Player.vue';
import {ref} from "vue";
import playerProps from "~/components/Public/playerProps";

const props = defineProps({
    ...playerProps,
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
});

const history = ref({});

const onNowPlayingUpdate = (newNowPlaying) => {
    history.value = newNowPlaying?.song_history;
}
</script>
