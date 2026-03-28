<template>
    <div class="public-page">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title mb-3">
                    {{ stationName }}
                </h2>

                <div class="stations nowplaying">
                    <radio-player
                        v-bind="player"
                        @np_updated="onNowPlayingUpdate"
                    />
                </div>
            </div>
            <div class="card-body buttons pt-0">
                <a
                    v-if="widgetCustomization.showHistoryButton"
                    class="btn btn-link text-secondary"
                    @click.prevent="openSongHistoryModal"
                >
                    <icon-ic-history/>

                    <span>
                        {{ $gettext('Song History') }}
                    </span>
                </a>
                <a
                    v-if="enableRequests && widgetCustomization.showRequestButton"
                    class="btn btn-link text-secondary"
                    @click.prevent="openRequestModal"
                >
                    <icon-ic-help/>

                    <span>
                        {{ $gettext('Request Song') }}
                    </span>
                </a>
                <a
                    v-if="widgetCustomization.showPlaylistButton"
                    class="btn btn-link text-secondary"
                    :href="downloadPlaylistUri"
                >
                    <icon-ic-cloud-download/>

                    <span>
                        {{ $gettext('Playlist') }}
                    </span>
                </a>
            </div>
        </div>
    </div>

    <song-history-modal
        ref="$songHistoryModal"
        :show-album-art="player.showAlbumArt"
        :history="history"
    />

    <request-modal
        v-if="enableRequests"
        ref="$requestModal"
        v-bind="requests"
    />

    <lightbox ref="$lightbox" />
</template>

<script setup lang="ts">
import SongHistoryModal from "~/components/Public/FullPlayer/SongHistoryModal.vue";
import RequestModal from "~/components/Public/FullPlayer/RequestModal.vue";
import RadioPlayer, {PlayerProps} from "~/components/Public/Player.vue";
import {computed, shallowRef, useTemplateRef} from "vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import {useProvideLightbox} from "~/vendor/lightbox";
import {RequestsProps} from "~/components/Public/Requests.vue";
import {ApiNowPlaying, ApiNowPlayingSongHistory} from "~/entities/ApiInterfaces.ts";
import IconIcCloudDownload from "~icons/ic/baseline-cloud-download";
import IconIcHelp from "~icons/ic/baseline-help";
import IconIcHistory from "~icons/ic/baseline-history";
import {defaultWidgetSettings} from "~/entities/PublicPlayer.ts";

const props = defineProps<{
    stationName: string,
    enableRequests?: boolean,
    downloadPlaylistUri: string,
    player: PlayerProps,
    requests: RequestsProps
}>();

const widgetCustomization = computed(
    () => props.player.widgetCustomization ?? defaultWidgetSettings
);


const history = shallowRef<ApiNowPlayingSongHistory[]>([]);

const onNowPlayingUpdate = (newNowPlaying: ApiNowPlaying) => {
    history.value = newNowPlaying?.song_history;
}

const $songHistoryModal = useTemplateRef('$songHistoryModal');

const openSongHistoryModal = () => {
    $songHistoryModal.value?.open();
}

const $requestModal = useTemplateRef('$requestModal');

const openRequestModal = () => {
    $requestModal.value?.open();
}

const $lightbox = useTemplateRef('$lightbox');

useProvideLightbox($lightbox);
</script>
