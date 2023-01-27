<template>
    <div class="public-page">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    {{ stationName }}
                </h2>

                <div class="stations nowplaying">
                    <radio-player
                        v-bind="pickProps(props, playerProps)"
                        @np_updated="onNowPlayingUpdate"
                    />
                </div>
            </div>

            <div class="card-actions">
                <a
                    v-b-modal.song_history_modal
                    class="btn btn-sm btn-outline-secondary"
                >
                    <icon icon="history" />
                    {{ $gettext('Song History') }}
                </a>
                <a
                    v-if="enableRequests"
                    v-b-modal.request_modal
                    class="btn btn-sm btn-outline-secondary"
                >
                    <icon icon="help_outline" />
                    {{ $gettext('Request Song') }}
                </a>
                <a
                    class="btn btn-sm btn-outline-secondary"
                    :href="downloadPlaylistUri"
                >
                    <icon icon="file_download" />
                    {{ $gettext('Playlist') }}
                </a>
            </div>
        </div>
    </div>

    <song-history-modal
        :show-album-art="showAlbumArt"
        :history="history"
    />
    <request-modal
        :show-album-art="showAlbumArt"
        :request-list-uri="requestListUri"
        :custom-fields="customFields"
    />
</template>

<script setup>
import SongHistoryModal from './FullPlayer/SongHistoryModal';
import RequestModal from './FullPlayer/RequestModal';
import Icon from '~/components/Common/Icon';
import RadioPlayer from './Player.vue';
import {ref} from "vue";
import playerProps from "~/components/Public/playerProps";
import {pickProps} from "~/functions/pickProps";

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
