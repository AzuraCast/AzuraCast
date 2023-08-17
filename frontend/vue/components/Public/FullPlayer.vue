<template>
    <div class="public-page">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title mb-3">
                    {{ stationName }}
                </h2>

                <div class="stations nowplaying">
                    <radio-player
                        v-bind="pickProps(props, playerProps)"
                        @np_updated="onNowPlayingUpdate"
                    />
                </div>
            </div>
            <div class="card-body buttons pt-0">
                <a
                    class="btn btn-link text-secondary"
                    @click.prevent="openSongHistoryModal"
                >
                    <icon icon="history" />
                    <span>
                        {{ $gettext('Song History') }}
                    </span>
                </a>
                <a
                    v-if="enableRequests"
                    class="btn btn-link text-secondary"
                    @click.prevent="openRequestModal"
                >
                    <icon icon="help_outline" />
                    <span>
                        {{ $gettext('Request Song') }}
                    </span>
                </a>
                <a
                    class="btn btn-link text-secondary"
                    :href="downloadPlaylistUri"
                >
                    <icon icon="file_download" />
                    <span>
                        {{ $gettext('Playlist') }}
                    </span>
                </a>
            </div>
        </div>
    </div>

    <song-history-modal
        ref="$songHistoryModal"
        :show-album-art="showAlbumArt"
        :history="history"
    />

    <request-modal
        ref="$requestModal"
        :show-album-art="showAlbumArt"
        :request-list-uri="requestListUri"
        :custom-fields="customFields"
    />

    <lightbox ref="$lightbox" />
</template>

<script setup>
import SongHistoryModal from './FullPlayer/SongHistoryModal';
import RequestModal from './FullPlayer/RequestModal';
import Icon from '~/components/Common/Icon';
import RadioPlayer from './Player.vue';
import {ref} from "vue";
import playerProps from "~/components/Public/playerProps";
import {pickProps} from "~/functions/pickProps";
import Lightbox from "~/components/Common/Lightbox.vue";
import {useProvideLightbox} from "~/vendor/lightbox";

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

const $songHistoryModal = ref(); // SongHistoryModal

const openSongHistoryModal = () => {
    $songHistoryModal.value.open();
}

const $requestModal = ref(); // RequestModal

const openRequestModal = () => {
    $requestModal.value.open();
}

const $lightbox = ref(); // Template Ref
useProvideLightbox($lightbox);
</script>
