<template>
    <card-page
        id="profile-nowplaying"
        class="nowplaying"
        header-id="hdr_now_playing"
    >
        <template #header="{id}">
            <div class="d-flex align-items-center">
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
                    <icon
                        class="align-middle"
                        :icon="IconHeadphones"
                    />
                    <span class="ps-1">
                        {{ langListeners }}
                    </span>

                    <br>
                    <small>
                        <span class="pe-1">{{ np.listeners.unique }}</span>
                        {{ $gettext('Unique') }}
                    </small>
                </h6>
                <router-link
                    v-if="userAllowedForStation(StationPermission.Reports)"
                    class="flex-shrink btn btn-link text-white ms-2 px-1 py-2"
                    :to="{name: 'stations:reports:listeners'}"
                    :title="$gettext('Listener Report')"
                >
                    <icon :icon="IconLogs" />
                </router-link>
            </div>
        </template>

        <div class="card-body">
            <loading :loading="np.loading">
                <div class="row">
                    <div class="col-md-6">
                        <div class="clearfix">
                            <div class="d-table">
                                <div class="d-table-row">
                                    <div class="d-table-cell align-middle text-end pe-2 pb-2">
                                        <icon :icon="IconMusicNote" />
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
                                            v-if="np.now_playing.song.art"
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
                                                {{ offlineText ?? $gettext('Station Offline') }}
                                            </h5>
                                        </div>
                                        <div v-else-if="np.now_playing.song.title !== ''">
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
                                                {{ np.now_playing.song.text }}
                                            </h6>
                                        </div>
                                        <div v-if="np.now_playing.playlist">
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
                    <div class="col-md-6">
                        <div
                            v-if="!np.live.is_live && np.playing_next"
                            class="clearfix"
                        >
                            <div class="d-table">
                                <div class="d-table-row">
                                    <div class="d-table-cell align-middle pe-2 text-end pb-2">
                                        <icon :icon="IconSkipNext" />
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
                                            v-if="np.playing_next.song.art"
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
                                        <div v-if="np.playing_next.song.title !== ''">
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
                                                {{ np.playing_next.song.text }}
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
                            v-else-if="np.live.is_live"
                            class="clearfix"
                        >
                            <h6 style="margin-left: 22px;">
                                <icon :icon="IconMic" />
                                {{ $gettext('Live') }}
                            </h6>

                            <h4
                                class="media-heading"
                                style="margin-left: 22px;"
                            >
                                {{ np.live.streamer_name }}
                            </h4>
                        </div>
                    </div>
                </div>
            </loading>
        </div>

        <template
            v-if="isLiquidsoap && userAllowedForStation(StationPermission.Broadcasting)"
            #footer_actions
        >
            <button
                v-if="!np.live.is_live"
                id="btn_skip_song"
                type="button"
                class="btn btn-link text-primary"
                @click="makeApiCall(backendSkipSongUri)"
            >
                <icon :icon="IconSkipNext" />
                <span>
                    {{ $gettext('Skip Song') }}
                </span>
            </button>
            <button
                v-if="np.live.is_live"
                id="btn_disconnect_streamer"
                type="button"
                class="btn btn-link text-primary"
                @click="makeApiCall(backendDisconnectStreamerUri)"
            >
                <icon :icon="IconVolumeOff" />
                <span>
                    {{ $gettext('Disconnect Streamer') }}
                </span>
            </button>
        </template>
    </card-page>
</template>

<script setup lang="ts">
import {BackendAdapter} from '~/entities/RadioAdapters';
import Icon from '~/components/Common/Icon.vue';
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import nowPlayingPanelProps from "~/components/Stations/Profile/nowPlayingPanelProps";
import useNowPlaying from "~/functions/useNowPlaying";
import Loading from "~/components/Common/Loading.vue";
import CardPage from "~/components/Common/CardPage.vue";
import {useLightbox} from "~/vendor/lightbox";
import {StationPermission, userAllowedForStation} from "~/acl";
import {useAzuraCastStation} from "~/vendor/azuracast";
import {IconHeadphones, IconLogs, IconMic, IconMusicNote, IconSkipNext, IconVolumeOff} from "~/components/Common/icons";

const props = defineProps({
    ...nowPlayingPanelProps,
});

const emit = defineEmits(['api-call']);

const {offlineText} = useAzuraCastStation();

const {
    np,
    currentTrackDurationDisplay,
    currentTrackElapsedDisplay
} = useNowPlaying(props);

const {$ngettext} = useTranslate();

const langListeners = computed(() => {
    return $ngettext(
        '%{listeners} Listener',
        '%{listeners} Listeners',
        np.value?.listeners?.total ?? 0,
        {listeners: np.value?.listeners?.total ?? 0}
    );
});

const isLiquidsoap = computed(() => {
    return props.backendType === BackendAdapter.Liquidsoap;
});

const {vLightbox} = useLightbox();

const makeApiCall = (uri) => {
    emit('api-call', uri);
};
</script>
