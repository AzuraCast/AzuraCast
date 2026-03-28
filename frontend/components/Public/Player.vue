<template>
    <div class="radio-player-widget">
        <div class="now-playing-details">
            <div
                v-if="computedShowAlbumArt && np.now_playing?.song?.art"
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
                    v-if="currentTrackElapsedDisplay != null && props.widgetCustomization?.showTrackProgress"
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
                    v-if="streams.length > 1 && props.widgetCustomization?.showStreamSelection"
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
                v-if="showPopupButton"
                class="radio-control-popup"
            >
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="openPopupPlayer"
                >
                    {{ $gettext('Open Popup Player') }}
                </button>
            </div>

            <div
                v-if="showVolume && props.widgetCustomization?.showVolumeControls"
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
import PlayButton from "~/components/Common/Audio/PlayButton.vue";
import {computed, nextTick, onMounted, ref, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useNowPlaying from "~/functions/useNowPlaying";
import MuteButton from "~/components/Common/Audio/MuteButton.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {blankStreamDescriptor, StreamDescriptor, usePlayerStore} from "~/functions/usePlayerStore.ts";
import {useEventListener} from "@vueuse/core";
import {ApiNowPlaying, ApiNowPlayingVueProps, ApiWidgetCustomization} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";
import {defaultWidgetSettings} from "~/entities/PublicPlayer.ts";
import useOptionalStorage from "~/functions/useOptionalStorage.ts";

export interface PlayerProps {
    nowPlayingProps: ApiNowPlayingVueProps,
    offlineText?: string,
    showHls?: boolean,
    showAlbumArt?: boolean,
    widgetCustomization?: ApiWidgetCustomization
}

const props = withDefaults(
    defineProps<PlayerProps>(),
    {
        showHls: true,
        showAlbumArt: true,
        widgetCustomization: () => defaultWidgetSettings
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
} = useNowPlaying(toRef(props, 'nowPlayingProps'));

const isPopupContext = new URLSearchParams(window.location.search).has('popup');

// Widget customization computed properties
const computedShowAlbumArt = computed(() => {
    return props.showAlbumArt && (props.widgetCustomization?.showAlbumArt ?? true);
});

const playerStore = usePlayerStore();
const {volume, showVolume, isMuted, isPlaying} = storeToRefs(playerStore);
const {setVolume, toggleMute, toggle} = playerStore;

// Set initial volume if specified
if (typeof props.widgetCustomization?.initialVolume === 'number' && props.widgetCustomization.initialVolume !== 75) {
    setVolume(props.widgetCustomization.initialVolume);
}

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

const popupUrl = computed(() => {
    if (!props.widgetCustomization?.enablePopupPlayer) {
        return null;
    }

    const popupTarget = new URL(window.location.href);
    popupTarget.searchParams.set('popup', '1');
    return popupTarget.toString();
});

const showPopupButton = computed(() => {
    return Boolean(popupUrl.value) && !isPopupContext;
});

const openPopupPlayer = () => {
    if (!popupUrl.value) {
        return;
    }

    window.open(
        popupUrl.value,
        'azuracast-player-popup',
        'width=540,height=760,resizable=yes,scrollbars=yes'
    );
};

const continuousPlayEnabled = computed(() => {
    return Boolean(props.widgetCustomization?.continuousPlay);
});

type ContinuousStorage = {
    streamUrl: string | null,
    isPlaying: boolean,
    resume: boolean
};

const blankContinuousStorage: ContinuousStorage = {
    streamUrl: null,
    isPlaying: false,
    resume: false
}

const continuousStorage = useOptionalStorage<ContinuousStorage>(
    () => `azuracast-player-state-${props.nowPlayingProps.stationShortName}`,
    blankContinuousStorage
);

watch(continuousPlayEnabled, (enabled) => {
    if (!enabled) {
        continuousStorage.value = blankContinuousStorage;
    }
}, {immediate: true});

watch(
    () => activeStream.value?.url ?? null,
    (streamUrl) => {
        if (continuousPlayEnabled.value) {
            continuousStorage.value.streamUrl = streamUrl;
        }
    }
);

watch(isPlaying, (playing) => {
    if (continuousPlayEnabled.value) {
        continuousStorage.value.isPlaying = playing;
    }
});

const urlParamVolume = (new URL(document.location.href)).searchParams.get('volume');
if (null !== urlParamVolume) {
    setVolume(Number(urlParamVolume));
}

if (props.widgetCustomization?.autoplay) {
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

    if (continuousPlayEnabled.value) {
        if (continuousStorage.value.streamUrl) {
            const matchingStream = $streams.find((stream) => stream.url === continuousStorage.value.streamUrl);
            if (matchingStream) {
                activeStream.value = matchingStream;
            }

            continuousStorage.value.streamUrl = null;
        }

        if (continuousStorage.value.resume) {
            if (activeStream.value.url && !isPlaying.value) {
                toggle(activeStream.value);
            }

            continuousStorage.value.resume = false;
        }
    }
};

watch(np, onNowPlayingUpdated, {immediate: true});
</script>

<style lang="scss">
.radio-player-widget {
    // CSS Custom Properties for widget customization
    --widget-primary-color: #2196F3;
    --widget-bg-color: transparent;
    --widget-text-color: inherit;
    --widget-border-radius: 0px;
    --widget-padding: 0;
    --widget-gap: 0.75rem;

    transition: all 0.3s ease;
    padding: var(--widget-padding);
    background-color: var(--widget-bg-color);
    color: var(--widget-text-color);
    border-radius: var(--widget-border-radius);

    .now-playing-details {
        display: flex;
        align-items: center;
        gap: var(--widget-gap);

        .now-playing-art {
            flex: 0 0 auto;

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
                    background-color: var(--widget-primary-color) !important;
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
        gap: var(--widget-gap);

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

        .radio-control-popup {
            flex: 0 0 auto;

            button {
                white-space: nowrap;
            }
        }
    }
}
</style>
