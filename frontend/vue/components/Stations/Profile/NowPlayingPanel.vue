<template>
    <section
        id="profile-nowplaying"
        class="card mb-4 nowplaying"
        role="region"
    >
        <div class="card-header bg-primary-dark">
            <div class="d-flex align-items-center">
                <h3 class="flex-shrink card-title my-0">
                    {{ $gettext('On the Air') }}
                </h3>
                <h6
                    class="card-subtitle text-right flex-fill my-0"
                    style="line-height: 1;"
                >
                    <icon
                        class="sm align-middle"
                        icon="headset"
                    />
                    <span class="pl-1">
                        {{ langListeners }}
                    </span>

                    <br>
                    <small>
                        <span class="pr-1">{{ np.listeners.unique }}</span>
                        {{ $gettext('Unique') }}
                    </small>
                </h6>
            </div>
        </div>
        <b-overlay
            variant="card"
            :show="np.loading"
        >
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="clearfix">
                            <h6 style="margin-left: 32px;">
                                <icon icon="music_note" />
                                {{ $gettext('Now Playing') }}
                            </h6>
                            <div class="media">
                                <a
                                    v-if="np.now_playing.song.art"
                                    class="mr-2"
                                    :href="np.now_playing.song.art"
                                    data-fancybox
                                    target="_blank"
                                >
                                    <img
                                        class="rounded"
                                        :src="np.now_playing.song.art"
                                        alt="Album Art"
                                        style="width: 50px;"
                                    >
                                </a>
                                <div class="media-body">
                                    <div v-if="!np.is_online">
                                        <h5 class="media-heading m-0 text-muted">
                                            {{ $gettext('Station Offline') }}
                                        </h5>
                                    </div>
                                    <div v-else-if="np.now_playing.song.title !== ''">
                                        <h5
                                            class="media-heading m-0"
                                            style="line-height: 1;"
                                        >
                                            {{ np.now_playing.song.title }}<br>
                                            <small>{{ np.now_playing.song.artist }}</small>
                                        </h5>
                                    </div>
                                    <div v-else>
                                        <h5
                                            class="media-heading m-0"
                                            style="line-height: 1;"
                                        >
                                            {{ np.now_playing.song.text }}
                                        </h5>
                                    </div>
                                    <div v-if="np.now_playing.playlist">
                                        <small class="text-muted">
                                            {{ $gettext('Playlist') }}
                                            : {{ np.now_playing.playlist }}</small>
                                    </div>
                                    <div
                                        v-if="timeDisplay"
                                        class="nowplaying-progress"
                                    >
                                        <small>{{ timeDisplay }}</small>
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
                            <h6 style="margin-left: 22px;">
                                <icon icon="skip_next" />
                                {{ $gettext('Playing Next') }}
                            </h6>

                            <div class="media">
                                <a
                                    v-if="np.playing_next.song.art"
                                    class="mr-2"
                                    :href="np.playing_next.song.art"
                                    data-fancybox
                                    target="_blank"
                                >
                                    <img
                                        :src="np.playing_next.song.art"
                                        class="rounded"
                                        alt="Album Art"
                                        style="width: 40px;"
                                    >
                                </a>
                                <div class="media-body">
                                    <div v-if="np.playing_next.song.title !== ''">
                                        <h5
                                            class="media-heading m-0"
                                            style="line-height: 1;"
                                        >
                                            {{ np.playing_next.song.title }}<br>
                                            <small>{{ np.playing_next.song.artist }}</small>
                                        </h5>
                                    </div>
                                    <div v-else>
                                        <h5
                                            class="media-heading m-0"
                                            style="line-height: 1;"
                                        >
                                            {{ np.playing_next.song.text }}
                                        </h5>
                                    </div>

                                    <div v-if="np.playing_next.playlist">
                                        <small class="text-muted">
                                            {{ $gettext('Playlist') }}
                                            : {{ np.playing_next.playlist }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else-if="np.live.is_live"
                            class="clearfix"
                        >
                            <h6 style="margin-left: 22px;">
                                <icon icon="mic" />
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
            </div>
        </b-overlay>

        <div
            v-if="isLiquidsoap && userCanManageBroadcasting"
            class="card-actions flex-shrink"
        >
            <a
                v-if="!np.live.is_live"
                id="btn_skip_song"
                class="btn btn-outline-primary api-call no-reload"
                role="button"
                :href="backendSkipSongUri"
            >
                <icon icon="skip_next" />
                {{ $gettext('Skip Song') }}
            </a>
            <a
                v-if="np.live.is_live"
                id="btn_disconnect_streamer"
                class="btn btn-outline-primary api-call no-reload"
                role="button"
                :href="backendDisconnectStreamerUri"
            >
                <icon icon="volume_off" />
                {{ $gettext('Disconnect Streamer') }}
            </a>
        </div>
    </section>
</template>

<script setup>
import {BACKEND_LIQUIDSOAP} from '~/components/Entity/RadioAdapters';
import Icon from '~/components/Common/Icon';
import {computed, onMounted, ref} from "vue";
import {useIntervalFn} from "@vueuse/core";
import {useTranslate} from "~/vendor/gettext";
import formatTime from "~/functions/formatTime";
import nowPlayingPanelProps from "~/components/Stations/Profile/nowPlayingPanelProps";

const props = defineProps({
    ...nowPlayingPanelProps,
    np: {
        type: Object,
        required: true
    }
});

const npElapsed = ref(0);

onMounted(() => {
    useIntervalFn(
        () => {
            let current_time = Math.floor(Date.now() / 1000);
            let np_elapsed = current_time - props.np.now_playing.played_at;
            if (np_elapsed < 0) {
                np_elapsed = 0;
            } else if (np_elapsed >= props.np.now_playing.duration) {
                np_elapsed = props.np.now_playing.duration;
            }

            npElapsed.value = np_elapsed;
        },
        1000
    );
});

const {$ngettext} = useTranslate();

const langListeners = computed(() => {
    return $ngettext(
        '%{listeners} Listener',
        '%{listeners} Listeners',
        props.np.listeners.total,
        {listeners: props.np.listeners.total}
    );
});

const isLiquidsoap = computed(() => {
    return props.backendType === BACKEND_LIQUIDSOAP;
});

const timeDisplay = computed(() => {
    let time_played = npElapsed.value;
    let time_total = props.np.now_playing.duration;

    if (!time_total) {
        return null;
    }

    if (time_played > time_total) {
        time_played = time_total;
    }

    return formatTime(time_played) + ' / ' + formatTime(time_total);
});
</script>
