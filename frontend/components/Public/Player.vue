<template>
    <div 
        class="radio-player-widget"
        :class="widgetClasses"
        :style="widgetStyles"
    >
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
import {computed, nextTick, onMounted, onUnmounted, ref, toRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useNowPlaying from "~/functions/useNowPlaying";
import MuteButton from "~/components/Common/Audio/MuteButton.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {blankStreamDescriptor, StreamDescriptor, usePlayerStore} from "~/functions/usePlayerStore.ts";
import {useEventListener} from "@vueuse/core";
import {ApiNowPlaying, ApiNowPlayingVueProps, ApiWidgetCustomization} from "~/entities/ApiInterfaces.ts";
import {storeToRefs} from "pinia";

export interface PlayerProps {
    nowPlayingProps: ApiNowPlayingVueProps,
    offlineText?: string,
    showHls?: boolean,
    showAlbumArt?: boolean,
    autoplay?: boolean,
    widgetCustomization?: ApiWidgetCustomization
}

defineOptions({
    inheritAttrs: false
});

const props = withDefaults(
    defineProps<PlayerProps>(),
    {
        showHls: true,
        showAlbumArt: true,
        autoplay: true,
        widgetCustomization: () => ({
            showAlbumArt: true,
            roundedCorners: false,
            autoplay: false,
            showVolumeControls: true,
            showTrackProgress: true,
            showStreamSelection: true,
            showHistoryButton: false,
            showRequestButton: false,
            initialVolume: 75,
            layout: 'horizontal',
            enablePopupPlayer: false,
            continuousPlay: false,
            customCss: '',
            primaryColor: undefined,
            backgroundColor: undefined,
            textColor: undefined
        })
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

const isClient = typeof window !== 'undefined';
const isPopupContext = isClient
    ? new URLSearchParams(window.location.search).has('popup')
    : false;

const popupLayout = computed(() => props.widgetCustomization?.layout ?? 'horizontal');

let previousBodyMargin = '';
let previousBodyOverflow = '';
let previousHtmlOverflow = '';

const applyLayoutScrollMode = (layout: string) => {
    if (!isClient) {
        return;
    }

    const requiresScroll = layout === 'vertical' || layout === 'large';

    if (requiresScroll) {
        document.body.classList.add('embed-player-scrollable');
        document.body.style.overflow = 'auto';
        document.documentElement.style.overflow = 'auto';
    } else {
        document.body.classList.remove('embed-player-scrollable');
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
    }
};

onMounted(() => {
    if (!isClient) {
        return;
    }

    previousBodyMargin = document.body.style.margin;
    previousBodyOverflow = document.body.style.overflow;
    previousHtmlOverflow = document.documentElement.style.overflow;

    document.body.classList.add('embed-player');
    if (isPopupContext) {
        document.body.classList.add('embed-player-popup');
    }

    document.body.style.margin = '0';
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';

    applyLayoutScrollMode(popupLayout.value);
});

onUnmounted(() => {
    if (!isClient) {
        return;
    }

    document.body.classList.remove('embed-player', 'embed-player-popup');
    document.body.classList.remove('embed-player-scrollable');
    document.body.style.margin = previousBodyMargin;
    document.body.style.overflow = previousBodyOverflow;
    document.documentElement.style.overflow = previousHtmlOverflow;
});

watch(popupLayout, (layout) => {
    applyLayoutScrollMode(layout);
});

// Widget customization computed properties
const computedShowAlbumArt = computed(() => {
    return props.showAlbumArt && (props.widgetCustomization?.showAlbumArt ?? true);
});

const widgetClasses = computed(() => {
    const classes: string[] = [];
    
    if (props.widgetCustomization?.layout) {
        classes.push(`layout-${props.widgetCustomization.layout}`);
    }
    
    if (props.widgetCustomization?.roundedCorners) {
        classes.push('rounded-corners');
    }

    if (isPopupContext) {
        classes.push('popup-context');
    }
    
    return classes;
});

const widgetStyles = computed(() => {
    const styles: Record<string, string> = {};
    
    if (props.widgetCustomization?.primaryColor) {
        styles['--widget-primary-color'] = `#${props.widgetCustomization.primaryColor}`;
    }
    
    if (props.widgetCustomization?.backgroundColor) {
        styles['--widget-bg-color'] = `#${props.widgetCustomization.backgroundColor}`;
    }
    
    if (props.widgetCustomization?.textColor) {
        styles['--widget-text-color'] = `#${props.widgetCustomization.textColor}`;
    }
    
    if (props.widgetCustomization?.roundedCorners) {
        styles['--widget-border-radius'] = '12px';
    }
    
    return styles;
});

// Inject custom CSS if provided
if (props.widgetCustomization?.customCss) {
    const customStyleElement = document.createElement('style');
    customStyleElement.textContent = props.widgetCustomization.customCss;
    document.head.appendChild(customStyleElement);
}

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
    if (!isClient || !props.widgetCustomization?.enablePopupPlayer) {
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
    if (!isClient || !popupUrl.value) {
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

const continuousStorageKey = `azuracast-player-state-${props.nowPlayingProps.stationShortName}`;
const desiredContinuousStreamUrl = ref<string | null>(null);
const pendingContinuousResume = ref(false);

const loadContinuousPreferences = () => {
    if (!continuousPlayEnabled.value || !isClient) {
        desiredContinuousStreamUrl.value = null;
        pendingContinuousResume.value = false;
        return;
    }

    try {
        const savedState = window.localStorage.getItem(continuousStorageKey);
        if (!savedState) {
            desiredContinuousStreamUrl.value = null;
            pendingContinuousResume.value = false;
            return;
        }

        const parsed = JSON.parse(savedState) as {streamUrl?: string | null, isPlaying?: boolean};
        desiredContinuousStreamUrl.value = (typeof parsed.streamUrl === 'string' && parsed.streamUrl.length > 0)
            ? parsed.streamUrl
            : null;
        pendingContinuousResume.value = Boolean(parsed.isPlaying);
    } catch (error) {
        console.warn('Failed to load player state for continuous playback.', error);
        desiredContinuousStreamUrl.value = null;
        pendingContinuousResume.value = false;
    }
};

watch(continuousPlayEnabled, (enabled) => {
    if (!isClient) {
        return;
    }

    if (enabled) {
        loadContinuousPreferences();
    } else {
        window.localStorage.removeItem(continuousStorageKey);
        desiredContinuousStreamUrl.value = null;
        pendingContinuousResume.value = false;
    }
}, {immediate: true});

watch([
    () => activeStream.value?.url ?? null,
    () => isPlaying.value
], ([streamUrl, playing]) => {
    if (!isClient || !continuousPlayEnabled.value) {
        return;
    }

    try {
        window.localStorage.setItem(continuousStorageKey, JSON.stringify({
            streamUrl,
            isPlaying: playing
        }));
    } catch (error) {
        console.warn('Failed to persist player state for continuous playback.', error);
    }
});

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

    if (continuousPlayEnabled.value) {
        if (desiredContinuousStreamUrl.value) {
            const matchingStream = $streams.find((stream) => stream.url === desiredContinuousStreamUrl.value);
            if (matchingStream) {
                activeStream.value = matchingStream;
            }
            desiredContinuousStreamUrl.value = null;
        }

        if (pendingContinuousResume.value && activeStream.value.url && !isPlaying.value) {
            toggle(activeStream.value);
        }

        pendingContinuousResume.value = false;
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
    --widget-padding: 1rem;
    --widget-gap: 0.75rem;
    
    transition: all 0.3s ease;
    padding: var(--widget-padding);
    background-color: var(--widget-bg-color);
    color: var(--widget-text-color);
    border-radius: var(--widget-border-radius);
    
    // Layout variants
    &.layout-vertical {
        .now-playing-details {
            flex-direction: column;
            text-align: center;
            
            .now-playing-art {
                margin-bottom: 1rem;
                margin-right: 0;
            }
        }
        
        .radio-controls {
            flex-direction: column;
            gap: 0.5rem;
            
            .radio-control-play-button {
                margin: 0 auto;
            }
        }
    }
    
    &.layout-compact {
        --widget-gap: 0.5rem;
        padding: 0.5rem;
        
        .now-playing-details {
            align-items: center;

            .now-playing-art {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;

                img {
                    width: 40px;
                    height: 40px;
                }
            }

            .now-playing-main {
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
        }
        
        .now-playing-title {
            font-size: 0.9rem;
        }
        
        .now-playing-artist {
            font-size: 0.8rem;
        }

        .radio-controls {
            gap: 0.5rem;
        }
    }
    
    &.layout-large {
        padding: 2rem;
        
        .now-playing-details {
            .now-playing-art {
                width: 120px;
                height: 120px;
                margin-right: 2rem;
            }
        }
        
        .now-playing-title {
            font-size: 1.5rem;
        }
        
        .now-playing-artist {
            font-size: 1.2rem;
        }
    }
    
    &.rounded-corners {
        border-radius: 12px;
        overflow: hidden;
    }

    &.popup-context {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: clamp(1rem, 2vw, 1.75rem);
        height: 100%;

        .now-playing-details {
            align-items: flex-start;
            gap: clamp(1.25rem, 3vw, 2rem);

            .now-playing-art img {
                width: clamp(96px, 18vw, 150px);
                height: clamp(96px, 18vw, 150px);
                object-fit: cover;
                border-radius: 12px;
            }
        }

        .radio-controls {
            margin-top: auto;
            width: 100%;
            padding-top: clamp(0.75rem, 2vw, 1.25rem);

            .radio-control-volume {
                .radio-control-volume-slider {
                    max-width: 60%;
                }
            }
        }
    }

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

body.embed-player {
    margin: 0;
    overflow: hidden;
    background: transparent;
}

body.embed-player-scrollable {
    overflow: auto;
}

body.embed-player-popup {
    --popup-padding: clamp(1.25rem, 3vw, 3rem);
    margin: 0;
    background: var(--bs-body-bg);
    display: flex;
    align-items: stretch;
    justify-content: center;
    padding: var(--popup-padding);
    min-height: 100vh;
    overflow: hidden;
}
body.embed-player-popup .radio-player-widget {
    width: min(640px, calc(100vw - (var(--popup-padding) * 2)));
    min-height: calc(100vh - (var(--popup-padding) * 2));
    max-height: 100vh;
    --widget-padding: clamp(1.25rem, 2.5vw, 2.25rem);
    --widget-gap: clamp(0.9rem, 1.8vw, 1.4rem);
    --widget-bg-color: var(--bs-card-bg);
    box-shadow: var(--bs-box-shadow-lg, 0 1.25rem 2.5rem rgba(15, 23, 42, 0.16));
    border: 1px solid var(--bs-border-color-translucent, rgba(15, 23, 42, 0.12));
}

body.embed-player-popup .radio-player-widget.rounded-corners {
    border-radius: 18px;
}

body.embed-player-scrollable .radio-player-widget {
    height: auto;
    min-height: auto;
    max-height: none;
}

body.embed-player-popup .radio-player-widget.layout-vertical {
    justify-content: flex-start;
}

@media (max-width: 575px) {
    body.embed-player-popup {
        --popup-padding: 1rem;
        --widget-padding: 1.25rem;
    }

    body.embed-player-popup .radio-player-widget {
        width: 100%;
        height: calc(100vh - (var(--popup-padding) * 2));
        box-shadow: var(--bs-box-shadow, 0 0.75rem 1.5rem rgba(15, 23, 42, 0.16));
    }
}
</style>
