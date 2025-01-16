<template>
    <div class="public-page">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title mb-3">
                    {{ stationName }}
                </h2>

                <div class="stations nowplaying">
                    <radio-player
                        v-bind="props"
                        @np_updated="onNowPlayingUpdate"
                    />
                </div>
            </div>
            <div class="card-body buttons pt-0">
                <a
                    class="btn btn-link text-secondary"
                    @click.prevent="openSongHistoryModal"
                >
                    <icon :icon="IconHistory" />
                    <span>
                        {{ $gettext('Song History') }}
                    </span>
                </a>
                <a
                    v-if="enableRequests"
                    class="btn btn-link text-secondary"
                    @click.prevent="openRequestModal"
                >
                    <icon :icon="IconHelp" />
                    <span>
                        {{ $gettext('Request Song') }}
                    </span>
                </a>
                <a
                    class="btn btn-link text-secondary"
                    :href="downloadPlaylistUri"
                >
                    <icon :icon="IconDownload" />
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
        v-if="enableRequests"
        ref="$requestModal"
        v-bind="props"
    />

    <lightbox ref="$lightbox" />
</template>

<script setup lang="ts">
import SongHistoryModal from './FullPlayer/SongHistoryModal.vue';
import RequestModal from './FullPlayer/RequestModal.vue';
import Icon from '~/components/Common/Icon.vue';
import RadioPlayer from './Player.vue';
import {ref, shallowRef} from "vue";
import Lightbox from "~/components/Common/Lightbox.vue";
import {LightboxTemplateRef, useProvideLightbox} from "~/vendor/lightbox";
import {IconDownload, IconHelp, IconHistory} from "~/components/Common/icons";
import {RequestsProps} from "~/components/Public/Requests.vue";
import {PlayerProps} from "~/components/Public/Player.vue";
import {ApiNowPlayingSongHistory} from "~/entities/ApiInterfaces.ts";

interface FullPlayerProps extends PlayerProps, RequestsProps {
    stationName: string,
    enableRequests?: boolean,
    downloadPlaylistUri: string
}

const props = withDefaults(
    defineProps<FullPlayerProps>(),
    {
        enableRequests: false
    }
);

const history = shallowRef<ApiNowPlayingSongHistory[]>([]);

const onNowPlayingUpdate = (newNowPlaying) => {
    history.value = newNowPlaying?.song_history;
}

const $songHistoryModal = ref<InstanceType<typeof SongHistoryModal> | null>(null);

const openSongHistoryModal = () => {
    $songHistoryModal.value?.open();
}

const $requestModal = ref<InstanceType<typeof RequestModal> | null>(null);

const openRequestModal = () => {
    $requestModal.value?.open();
}

const $lightbox = ref<LightboxTemplateRef>(null);
useProvideLightbox($lightbox);
</script>
