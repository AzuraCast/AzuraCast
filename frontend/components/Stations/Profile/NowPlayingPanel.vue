<template>
    <card-page
        id="profile-nowplaying"
        class="nowplaying"
        header-id="hdr_now_playing"
    >
        <template #header="{id}">
            <div class="d-flex align-items-center">
                <div v-if="profileData.station.listen_url" class="flex-shrink-0 me-2">
                    <play-button
                        class="btn-xl btn-link text-white"
                        :stream="{
                            url: profileData.station.listen_url,
                            title: stationData.name,
                            isStream: true
                        }"
                    />
                </div>
                <h3
                    :id="id"
                    class="flex-shrink card-title my-0"
                >
                    {{ $gettext('On the Air') }}
                </h3>
                <h6
                    class="card-subtitle text-end flex-fill my-0"
                    style="line-height: 1;"
                >
                    <icon-ic-headphones class="align-middle"/>
                    <span class="ps-1">
                        {{ langListeners }}
                    </span>

                    <br>
                    <small>
                        <span class="pe-1">{{ np.listeners?.unique ?? 0 }}</span>
                        {{ $gettext('Unique') }}
                    </small>
                </h6>
                <router-link
                    v-if="userAllowedForStation(StationPermissions.Reports)"
                    class="flex-shrink btn btn-link text-white ms-2 px-1 py-2"
                    :to="{name: 'stations:reports:listeners'}"
                    :title="$gettext('Listener Report')"
                >
                    <icon-ic-assignment/>
                </router-link>
            </div>
        </template>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="clearfix">
                        <div class="d-table">
                            <div class="d-table-row">
                                <div class="d-table-cell align-middle text-end pe-2 pb-2">
                                    <icon-ic-music-note/>
                                </div>
                                <div class="d-table-cell align-middle w-100 pb-2">
                                    <h5 class="m-0">
                                        {{ $gettext('Now Playing') }}
                                    </h5>
                                </div>
                            </div>
                            <div class="d-table-row">
                                <div class="d-table-cell align-top text-end pe-2">
                                    <a
                                        v-if="np.now_playing?.song?.art"
                                        v-lightbox
                                        :href="np.now_playing.song.art"
                                        target="_blank"
                                    >
                                        <img
                                            class="rounded"
                                            :src="np.now_playing.song.art"
                                            alt="Album Art"
                                            style="width: 50px;"
                                        >
                                    </a>
                                </div>
                                <div class="d-table-cell align-middle w-100">
                                    <div v-if="!np.is_online">
                                        <h5 class="media-heading m-0 text-muted">
                                            {{ stationData.offlineText ?? $gettext('Station Offline') }}
                                        </h5>
                                    </div>
                                    <div v-else-if="np.now_playing?.song?.title">
                                        <h6
                                            class="media-heading m-0"
                                            style="line-height: 1.2;"
                                        >
                                            {{ np.now_playing.song.title }}<br>
                                            <small class="text-muted">{{ np.now_playing.song.artist }}</small>
                                        </h6>
                                    </div>
                                    <div v-else>
                                        <h6
                                            class="media-heading m-0"
                                            style="line-height: 1.2;"
                                        >
                                            {{ np.now_playing?.song?.text ?? '' }}
                                        </h6>
                                    </div>
                                    <div v-if="np.now_playing?.playlist">
                                        <small class="text-muted">
                                            {{ $gettext('Playlist') }}
                                            : {{ np.now_playing.playlist }}</small>
                                    </div>
                                    <div
                                        v-if="currentTrackElapsedDisplay"
                                        class="nowplaying-progress"
                                    >
                                        <small>
                                            {{ currentTrackElapsedDisplay }} / {{ currentTrackDurationDisplay }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <div
                        v-if="!np.live?.is_live && np.playing_next"
                        class="clearfix"
                    >
                        <div class="d-table">
                            <div class="d-table-row">
                                <div class="d-table-cell align-middle pe-2 text-end pb-2">
                                    <icon-ic-skip-next/>
                                </div>
                                <div class="d-table-cell align-middle w-100 pb-2">
                                    <h5 class="m-0">
                                        {{ $gettext('Playing Next') }}
                                    </h5>
                                </div>
                            </div>
                            <div class="d-table-row">
                                <div class="d-table-cell align-top text-end pe-2">
                                    <a
                                        v-if="np.playing_next?.song?.art"
                                        v-lightbox
                                        :href="np.playing_next.song.art"
                                        target="_blank"
                                    >
                                        <img
                                            :src="np.playing_next.song.art"
                                            class="rounded"
                                            alt="Album Art"
                                            style="width: 40px;"
                                        >
                                    </a>
                                </div>
                                <div class="d-table-cell align-middle w-100">
                                    <div v-if="np.playing_next?.song?.title">
                                        <h6
                                            class="media-heading m-0"
                                            style="line-height: 1;"
                                        >
                                            {{ np.playing_next.song.title }}<br>
                                            <small class="text-muted">{{ np.playing_next.song.artist }}</small>
                                        </h6>
                                    </div>
                                    <div v-else>
                                        <h6
                                            class="media-heading m-0"
                                            style="line-height: 1;"
                                        >
                                            {{ np.playing_next?.song?.text ?? "" }}
                                        </h6>
                                    </div>

                                    <div v-if="np.playing_next.playlist">
                                        <small class="text-muted">
                                            {{ $gettext('Playlist') }}
                                            : {{ np.playing_next.playlist }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else-if="np.live?.is_live"
                        class="clearfix"
                    >
                        <h6 style="margin-left: 22px;">
                            <icon-ic-mic/>

                            {{ $gettext('Live') }}
                        </h6>

                        <h4
                            class="media-heading"
                            style="margin-left: 22px;"
                        >
                            {{ np.live?.streamer_name }}
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        <template
            v-if="isLiquidsoap && userAllowedForStation(StationPermissions.Broadcasting)"
            #footer_actions
        >
            <button
                v-if="!np.live?.is_live"
                id="btn_skip_song"
                type="button"
                class="btn btn-link text-primary"
                @click="doSkipSong()"
            >
                <icon-ic-skip-next/>

                <span>
                    {{ $gettext('Skip Song') }}
                </span>
            </button>
            <button
                v-if="np.live?.is_live"
                id="btn_disconnect_streamer"
                type="button"
                class="btn btn-link text-primary"
                @click="doDisconnectStreamer()"
            >
                <icon-ic-volume-off/>

                <span>
                    {{ $gettext('Disconnect Streamer') }}
                </span>
            </button>
            <button
                id="btn_update_metadata"
                type="button"
                class="btn btn-link text-secondary"
                @click="updateMetadata()"
            >
                <icon-ic-update/>

                <span>
                    {{ $gettext('Update Metadata') }}
                </span>
            </button>
        </template>
    </card-page>

    <template v-if="isLiquidsoap && userAllowedForStation(StationPermissions.Broadcasting)">
        <update-metadata-modal ref="$updateMetadataModal" />
    </template>
</template>

<script setup lang="ts">
import {computed, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useNowPlaying from "~/functions/useNowPlaying";
import CardPage from "~/components/Common/CardPage.vue";
import {useLightbox} from "~/vendor/lightbox";
import {useUserAllowedForStation} from "~/functions/useUserallowedForStation.ts";
import UpdateMetadataModal from "~/components/Stations/Profile/UpdateMetadataModal.vue";
import useMakeApiCall from "~/components/Stations/Profile/useMakeApiCall.ts";
import {BackendAdapters, StationPermissions} from "~/entities/ApiInterfaces.ts";
import {useStationData} from "~/functions/useStationQuery.ts";
import {useStationProfileData} from "~/components/Stations/Profile/useProfileQuery.ts";
import {toRefs} from "@vueuse/core";
import IconIcHeadphones from "~icons/ic/baseline-headphones";
import IconIcAssignment from "~icons/ic/baseline-assignment";
import IconIcMic from "~icons/ic/baseline-mic";
import IconIcMusicNote from "~icons/ic/baseline-music-note";
import IconIcSkipNext from "~icons/ic/baseline-skip-next";
import IconIcUpdate from "~icons/ic/baseline-update";
import IconIcVolumeOff from "~icons/ic/baseline-volume-off";
import {useApiRouter} from "~/functions/useApiRouter.ts";
import PlayButton from "~/components/Common/Audio/PlayButton.vue";

const stationData = useStationData();
const profileData = useStationProfileData();
const {nowPlayingProps} = toRefs(profileData);

const {userAllowedForStation} = useUserAllowedForStation();

const {getStationApiUrl} = useApiRouter();

const backendSkipSongUri = getStationApiUrl('/backend/skip');
const backendDisconnectStreamerUri = getStationApiUrl('/backend/disconnect');

const {
    np,
    currentTrackDurationDisplay,
    currentTrackElapsedDisplay
} = useNowPlaying(nowPlayingProps);

const {$gettext, $ngettext} = useTranslate();

const langListeners = computed(() => {
    return $ngettext(
        '%{listeners} Listener',
        '%{listeners} Listeners',
        np.value?.listeners?.total ?? 0,
        {
            listeners: String(np.value?.listeners?.total ?? 0)
        }
    );
});

const isLiquidsoap = computed(() => {
    return stationData.value.backendType === BackendAdapters.Liquidsoap;
});

const {vLightbox} = useLightbox();

const doSkipSong = useMakeApiCall(
    backendSkipSongUri,
    {
        title: $gettext('Skip current song?'),
        confirmButtonText: $gettext('Skip Song')
    }
);

const doDisconnectStreamer = useMakeApiCall(
    backendDisconnectStreamerUri,
    {
        title: $gettext('Disconnect current streamer?'),
        confirmButtonText: $gettext('Disconnect Streamer')
    }
);

const $updateMetadataModal = useTemplateRef('$updateMetadataModal');

const updateMetadata = () => {
    $updateMetadataModal.value?.open();
}
</script>
