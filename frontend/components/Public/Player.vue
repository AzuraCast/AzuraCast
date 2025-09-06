<template>
    <div class="radio-player-widget">
        <div class="now-playing-details">
            <div
                v-if="showAlbumArt && np.now_playing?.song?.art"
                class="now-playing-art"
            >
                <album-art :src="np.now_playing.song.art" />
            </div>
            <div class="now-playing-main">
                <h6
                    v-if="np.live?.is_live"
                    class="now-playing-live"
                >
                    <span class="badge text-bg-primary me-2">
                        {{ $gettext('Live') }}
                    </span>
                    {{ np.live.streamer_name }}
                </h6>

                <div v-if="!np.is_online">
                    <h4 class="now-playing-title text-muted">
                        {{ offlineText ?? $gettext('Station Offline') }}
                    </h4>
                </div>
                <div v-else-if="np.now_playing?.song?.title">
                    <h4 class="now-playing-title">
                        {{ np.now_playing.song.title }}
                    </h4>
                    <h5 class="now-playing-artist">
                        {{ np.now_playing.song.artist }}
                    </h5>
                </div>
                <div v-else>
                    <h4 class="now-playing-title">
                        {{ np.now_playing?.song?.text }}
                    </h4>
                </div>

                <div
                    v-if="currentTrackElapsedDisplay != null"
                    class="time-display"
                >
                    <div class="time-display-played text-secondary">
                        {{ currentTrackElapsedDisplay }}
                    </div>
                    <div class="time-display-progress">
                        <div class="progress h-5">
                            <div
                                class="progress-bar bg-secondary"
                                role="progressbar"
                                :style="{ width: currentTrackPercent+'%' }"
                            />
                        </div>
                    </div>
                    <div class="time-display-total text-secondary">
                        {{ currentTrackDurationDisplay }}
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-2">

        <div class="radio-controls">
            <play-button
                class="radio-control-play-button btn-xl"
                :stream="activeStream"
            />

            <div class="radio-control-select-stream">
                <div
                    v-if="streams.length > 1"
                    class="dropdown"
                >
                    <button
                        id="btn-select-stream"
                        class="btn btn-sm btn-secondary dropdown-toggle"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        {{ activeStream.title }}
                        <span class="caret" />
                    </button>
                    <ul
                        class="dropdown-menu"
                        aria-labelledby="btn-select-stream"
                    >
                        <li
                            v-for="stream in streams"
                            :key="stream.url ?? stream.title ?? 'Stream'"
                        >
                            <button
                                type="button"
                                class="dropdown-item"
                                @click="setActiveStream(stream)"
                            >
                                {{ stream.title }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                v-if="showVolume"
                class="radio-control-volume d-flex align-items-center"
            >
                <div class="flex-shrink-0 mx-2">
                    <mute-button
                        class="p-0 text-secondary"
                        :volume="volume"
                        :is-muted="isMuted"
                        @toggle-mute="toggleMute"
                    />
                </div>
                <div class="flex-fill radio-control-volume-slider">
                    <input
                        v-model.number="volume"
                        type="range"
                        :title="$gettext('Volume')"
                        class="form-range"
                        min="0"
                        max="100"
                        step="1"
                    >
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import PlayButton from "~/components/Common/PlayButton.vue";
import {computed, nextTick, onMounted, ref, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useNowPlaying from "~/functions/useNowPlaying";
import MuteButton from "~/components/Common/MuteButton.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {blankStreamDescriptor, StreamDescriptor, usePlayerStore} from "~/functions/usePlayerStore.ts";
import {useEventListener} from "@vueuse/core";
import {ApiNowPlaying} from "~/entities/ApiInterfaces.ts";
import {NowPlayingProps} from "~/functions/useNowPlaying.ts";
import {storeToRefs} from "pinia";

export interface PlayerProps extends NowPlayingProps {
    offlineText?: string,
    showHls?: boolean,
    showAlbumArt?: boolean,
    autoplay?: boolean
}

defineOptions({
    inheritAttrs: false
});

const props = withDefaults(
    defineProps<PlayerProps>(),
    {
        showHls: true,
        showAlbumArt: true,
        autoplay: true
    }
);

const emit = defineEmits<{
    (e: 'np_updated', np: ApiNowPlaying): void
}>();

const {
    np,
    currentTrackPercent,
    currentTrackDurationDisplay,
    currentTrackElapsedDisplay
} = useNowPlaying(props);

const playerStore = usePlayerStore();
const {volume, showVolume, isMuted} = storeToRefs(playerStore);
const {setVolume, toggleMute, toggle} = playerStore;

const enableHls = computed(() => {
    return props.showHls && np.value?.station?.hls_enabled;
});

const hlsIsDefault = computed(() => {
    return enableHls.value && np.value?.station?.hls_is_default;
});

const {$gettext} = useTranslate();

const activeStream = ref<StreamDescriptor>(blankStreamDescriptor);

const streams = computed<StreamDescriptor[]>(() => {
    const allStreams: StreamDescriptor[] = [];

    if (enableHls.value) {
        allStreams.push({
            title: $gettext('HLS'),
            url: np.value?.station?.hls_url,
            isStream: true,
            isHls: true
        });
    }

    np.value?.station?.mounts?.forEach(function (mount) {
        allStreams.push({
            title: mount.name ?? mount.url,
            url: mount.url,
            isStream: true,
            isHls: false
        });
    });

    np.value?.station?.remotes?.forEach(function (remote) {
        allStreams.push({
            title: remote.name ?? remote.url,
            url: remote.url,
            isStream: true,
            isHls: false
        });
    });

    return allStreams;
});

const setActiveStream = (newStream: StreamDescriptor): void => {
    activeStream.value = newStream;
    toggle(newStream);
};

const urlParamVolume = (new URL(document.location.href)).searchParams.get('volume');
if (null !== urlParamVolume) {
    setVolume(Number(urlParamVolume));
}

if (props.autoplay) {
    const cleanupEvent = useEventListener(document, "now-playing", () => {
        void nextTick(() => {
            toggle(activeStream.value);
            cleanupEvent();
        });
    });
}

onMounted(() => {
    document.dispatchEvent(new CustomEvent("player-ready"));
});

const onNowPlayingUpdated = (np_new: ApiNowPlaying) => {
    emit('np_updated', np_new);

    // Set a "default" current stream if none exists.
    const $streams = streams.value;
    let $currentStream: StreamDescriptor | null = activeStream.value;

    if ($currentStream.url === null && $streams.length > 0) {
        if (hlsIsDefault.value) {
            activeStream.value = $streams[0];
        } else {
            $currentStream = null;

            if (np_new.station?.listen_url) {
                $streams.forEach(function (stream) {
                    if (stream.url === np_new.station?.listen_url) {
                        $currentStream = stream;
                    }
                });
            }

            if ($currentStream === null) {
                $currentStream = $streams[0];
            }

            activeStream.value = $currentStream;
        }
    }
};

watch(np, onNowPlayingUpdated, {immediate: true});
</script>

<style lang="scss">
.radio-player-widget {
    .now-playing-details {
        display: flex;
        align-items: center;

        .now-playing-art {
            padding-right: .5rem;

            img {
                width: 75px;
                height: auto;
                border-radius: 5px;

                @media (max-width: 575px) {
                    width: 50px;
                }
            }
        }

        .now-playing-main {
            flex: 1;
            min-width: 0;
        }

        h4, h5, h6 {
            margin: 0;
            line-height: 1.3;
        }

        h4 {
            font-size: 15px;
        }

        h5 {
            font-size: 13px;
            font-weight: normal;
        }

        h6 {
            font-size: 11px;
            font-weight: normal;
        }

        .now-playing-title,
        .now-playing-artist {
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;

            &:hover {
                text-overflow: clip;
                white-space: normal;
                word-break: break-all;
            }
        }

        .time-display {
            font-size: 10px;
            margin-top: .25rem;
            flex-direction: row;
            align-items: center;
            display: flex;

            .time-display-played {
                margin-right: .5rem;
            }

            .time-display-progress {
                flex: 1 1 auto;

                .progress-bar {
                    -webkit-transition: width 1s; /* Safari */
                    transition: width 1s;
                    transition-timing-function: linear;
                }
            }

            .time-display-total {
                margin-left: .5rem;
            }
        }
    }

    .radio-controls {
        display: flex;
        flex-direction: row;
        align-items: center;
        flex-wrap: wrap;

        .radio-control-play-button {
            margin-right: .25rem;
        }

        .radio-control-select-stream {
            flex: 1 1 auto;
            max-width: 60%;

            #btn-select-stream {
                text-overflow: clip;
                white-space: normal;
                word-break: break-all;
            }
        }

        .radio-control-volume {
            .radio-control-volume-slider {
                max-width: 30%;
            }
        }
    }
}
</style>
