<template>
    <div class="radio-player-widget">
        <audio-player
            ref="$player"
            :title="np.now_playing.song.text"
            :volume="volume"
            :is-muted="isMuted"
        />

        <div class="now-playing-details">
            <div
                v-if="showAlbumArt && np.now_playing.song.art"
                class="now-playing-art"
            >
                <album-art :src="np.now_playing.song.art" />
            </div>
            <div class="now-playing-main">
                <h6
                    v-if="np.live.is_live"
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
                <div v-else-if="np.now_playing.song.title !== ''">
                    <h4 class="now-playing-title">
                        {{ np.now_playing.song.title }}
                    </h4>
                    <h5 class="now-playing-artist">
                        {{ np.now_playing.song.artist }}
                    </h5>
                </div>
                <div v-else>
                    <h4 class="now-playing-title">
                        {{ np.now_playing.song.text }}
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
                :url="currentStream.url"
                :is-hls="currentStream.hls"
                is-stream
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
                        {{ currentStream.name }}
                        <span class="caret" />
                    </button>
                    <ul
                        class="dropdown-menu"
                        aria-labelledby="btn-select-stream"
                    >
                        <li
                            v-for="stream in streams"
                            :key="stream.url"
                        >
                            <button
                                type="button"
                                class="dropdown-item"
                                @click="switchStream(stream)"
                            >
                                {{ stream.name }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="radio-control-volume d-flex align-items-center">
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

<script setup>
import AudioPlayer from '~/components/Common/AudioPlayer';
import PlayButton from "~/components/Common/PlayButton";
import {computed, onMounted, ref, shallowRef, watch} from "vue";
import {useTranslate} from "~/vendor/gettext";
import useNowPlaying from "~/functions/useNowPlaying";
import playerProps from "~/components/Public/playerProps";
import MuteButton from "~/components/Common/MuteButton.vue";
import AlbumArt from "~/components/Common/AlbumArt.vue";
import {useAzuraCastStation} from "~/vendor/azuracast";
import usePlayerVolume from "~/functions/usePlayerVolume";

const props = defineProps({
    ...playerProps
});

const emit = defineEmits(['np_updated']);

const {offlineText} = useAzuraCastStation();

const {
    np,
    currentTrackPercent,
    currentTrackDurationDisplay,
    currentTrackElapsedDisplay
} = useNowPlaying(props);

const currentStream = shallowRef({
    'name': '',
    'url': '',
    'hls': false,
});

const enable_hls = computed(() => {
    const $np = np.value;
    return props.showHls && $np.station.hls_enabled;
});

const {$gettext} = useTranslate();

const streams = computed(() => {
    const allStreams = [];
    const $np = np.value;

    if (enable_hls.value) {
        allStreams.push({
            'name': $gettext('HLS'),
            'url': $np.station.hls_url,
            'hls': true,
        });
    }

    $np.station.mounts.forEach(function (mount) {
        allStreams.push({
            'name': mount.name,
            'url': mount.url,
            'hls': false,
        });
    });
    $np.station.remotes.forEach(function (remote) {
        allStreams.push({
            'name': remote.name,
            'url': remote.url,
            'hls': false,
        });
    });

    return allStreams;
});

const $player = ref(); // Template ref

const volume = usePlayerVolume();

const urlParamVolume = (new URL(document.location)).searchParams.get('volume');
if (null !== urlParamVolume) {
    volume.value = urlParamVolume;
}

const isMuted = ref(false);

const toggleMute = () => {
    isMuted.value = !isMuted.value;
}

const switchStream = (new_stream) => {
    currentStream.value = new_stream;
    $player.value.toggle(new_stream.url, true, new_stream.hls);
};

onMounted(() => {
    document.dispatchEvent(new CustomEvent("player-ready"));

    if (props.autoplay) {
        switchStream(currentStream.value);
    }
});

const onNowPlayingUpdated = (np_new) => {
    emit('np_updated', np_new);

    // Set a "default" current stream if none exists.
    const $streams = streams.value;
    let $currentStream = currentStream.value;

    if ($currentStream.url === '' && $streams.length > 0) {
        if (props.hlsIsDefault && enable_hls.value) {
            currentStream.value = $streams[0];
        } else {
            $currentStream = null;

            if (np_new.station.listen_url !== '') {
                $streams.forEach(function (stream) {
                    if (stream.url === np_new.station.listen_url) {
                        $currentStream = stream;
                    }
                });
            }

            if ($currentStream === null) {
                $currentStream = $streams[0];
            }

            currentStream.value = $currentStream;
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
