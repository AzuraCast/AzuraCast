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
                <a
                    :href="np.now_playing.song.art"
                    data-fancybox
                    target="_blank"
                >
                    <img
                        :src="np.now_playing.song.art"
                        :alt="$gettext('Album Art')"
                    >
                </a>
            </div>
            <div class="now-playing-main">
                <h6
                    v-if="np.live.is_live"
                    class="now-playing-live"
                >
                    {{ $gettext('Live') }}
                    {{ np.live.streamer_name }}
                </h6>

                <div v-if="!np.is_online">
                    <h4 class="now-playing-title text-muted">
                        {{ $gettext('Station Offline') }}
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
                    v-if="currentTimeElapsedDisplay != null"
                    class="time-display"
                >
                    <div class="time-display-played text-secondary">
                        {{ currentTimeElapsedDisplay }}
                    </div>
                    <div class="time-display-progress">
                        <div class="progress">
                            <div
                                class="progress-bar bg-secondary"
                                role="progressbar"
                                :style="{ width: currentTrackPercent+'%' }"
                            />
                        </div>
                    </div>
                    <div class="time-display-total text-secondary">
                        {{ currentTimeTotalDisplay }}
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="radio-controls">
            <play-button
                class="radio-control-play-button"
                icon-class="outlined lg"
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
                        class="btn btn-sm btn-outline-primary dropdown-toggle"
                        type="button"
                        data-toggle="dropdown"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        {{ currentStream.name }}
                    </button>
                    <div
                        class="dropdown-menu"
                        aria-labelledby="btn-select-stream"
                    >
                        <a
                            v-for="stream in streams"
                            class="dropdown-item"
                            href="javascript:"
                            @click.prevent="switchStream(stream)"
                        >
                            {{ stream.name }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="radio-control-mute-button">
                <a
                    href="#"
                    class="text-secondary"
                    :title="$gettext('Mute')"
                    @click.prevent="toggleMute"
                >
                    <icon icon="volume_mute" />
                </a>
            </div>
            <div class="radio-control-volume-slider">
                <input
                    v-model.number="volume"
                    type="range"
                    :title="$gettext('Volume')"
                    class="custom-range"
                    min="0"
                    max="100"
                    step="1"
                    :disabled="isMuted"
                >
            </div>
            <div class="radio-control-max-volume-button">
                <a
                    href="#"
                    class="text-secondary"
                    :title="$gettext('Full Volume')"
                    @click.prevent="fullVolume"
                >
                    <icon icon="volume_up" />
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import AudioPlayer from '~/components/Common/AudioPlayer';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import {computed, onMounted, ref, shallowRef, watch} from "vue";
import {useMounted, useStorage} from "@vueuse/core";
import formatTime from "~/functions/formatTime";
import {useTranslate} from "~/vendor/gettext";
import useNowPlaying from "~/functions/useNowPlaying";
import playerProps from "~/components/Public/playerProps";

const props = defineProps({
    ...playerProps
});

const emit = defineEmits(['np_updated']);

const {np, currentTrackElapsed, currentTrackDuration} = useNowPlaying(props);

const currentStream = shallowRef({
    'name': '',
    'url': '',
    'hls': false,
});

const enable_hls = computed(() => {
    let $np = np.value;
    return props.showHls && $np.station.hls_enabled;
});

const {$gettext} = useTranslate();

const streams = computed(() => {
    let allStreams = [];
    let $np = np.value;

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

const currentTrackPercent = computed(() => {
    let $currentTrackElapsed = currentTrackElapsed.value;
    let $currentTrackDuration = currentTrackDuration.value;

    if (!$currentTrackDuration) {
        return 0;
    }
    if ($currentTrackElapsed > $currentTrackDuration) {
        return 100;
    }

    return ($currentTrackElapsed / $currentTrackDuration) * 100;
});

const currentTimeElapsedDisplay = computed(() => {
    let $currentTrackElapsed = currentTrackElapsed.value;
    let $currentTrackDuration = currentTrackDuration.value;

    if (!$currentTrackDuration) {
        return null;
    }

    if ($currentTrackElapsed > $currentTrackDuration) {
        $currentTrackElapsed = $currentTrackDuration;
    }

    return formatTime($currentTrackElapsed);
});

const currentTimeTotalDisplay = computed(() => {
    let $currentTrackDuration = currentTrackDuration.value;
    return ($currentTrackDuration) ? formatTime($currentTrackDuration) : null;
});

const $player = ref(); // Template ref

const volume = useStorage('player_volume', 55);
const isMuted = useStorage('player_is_muted', false);

const toggleMute = () => {
    isMuted.value = !isMuted.value;
}

const fullVolume = () => {
    volume.value = 100;
};

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
    let $streams = streams.value;
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

    hr {
        margin-top: .5rem;
        margin-bottom: .5rem;
    }

    i.material-icons {
        line-height: 1;
    }

    .radio-controls {
        display: flex;
        flex-direction: row;
        align-items: center;

        .radio-control-play-button {
            margin-right: .25rem;
        }

        .radio-control-select-stream {
            flex: 1 1 auto;
        }

        .radio-control-mute-button,
        .radio-control-max-volume-button {
            flex-shrink: 0;
        }

        .radio-control-volume-slider {
            flex: 1 1 auto;
            max-width: 30%;

            input {
                height: 10px;
            }
        }
    }
}
</style>
