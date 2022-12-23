<template>
    <div class="radio-player-widget">
        <audio-player ref="player" :title="np.now_playing.song.text" :volume="volume"
                      :is-muted="isMuted"></audio-player>

        <div class="now-playing-details">
            <div class="now-playing-art" v-if="showAlbumArt && np.now_playing.song.art">
                <a :href="np.now_playing.song.art" data-fancybox target="_blank">
                    <img :src="np.now_playing.song.art" :alt="$gettext('Album Art')">
                </a>
            </div>
            <div class="now-playing-main">
                <h6 class="now-playing-live" v-if="np.live.is_live">
                    {{ $gettext('Live') }}
                    {{ np.live.streamer_name }}
                </h6>

                <div v-if="!np.is_online">
                    <h4 class="now-playing-title text-muted">
                        {{ $gettext('Station Offline') }}
                    </h4>
                </div>
                <div v-else-if="np.now_playing.song.title !== ''">
                    <h4 class="now-playing-title">{{ np.now_playing.song.title }}</h4>
                    <h5 class="now-playing-artist">{{ np.now_playing.song.artist }}</h5>
                </div>
                <div v-else>
                    <h4 class="now-playing-title">{{ np.now_playing.song.text }}</h4>
                </div>

                <div class="time-display" v-if="time_display_played != null">
                    <div class="time-display-played text-secondary">
                        {{ time_display_played }}
                    </div>
                    <div class="time-display-progress">
                        <div class="progress">
                            <div class="progress-bar bg-secondary" role="progressbar"
                                 :style="{ width: time_percent+'%' }"></div>
                        </div>
                    </div>
                    <div class="time-display-total text-secondary">
                        {{ time_display_total }}
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="radio-controls">
            <play-button class="radio-control-play-button" icon-class="outlined lg" :url="current_stream.url"
                         :is-hls="current_stream.hls" is-stream></play-button>

            <div class="radio-control-select-stream">
                <div v-if="streams.length > 1" class="dropdown">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="btn-select-stream"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{ current_stream.name }}
                    </button>
                    <div class="dropdown-menu" aria-labelledby="btn-select-stream">
                        <a class="dropdown-item" v-for="stream in streams" href="javascript:"
                           @click.prevent="switchStream(stream)">
                            {{ stream.name }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="radio-control-mute-button">
                <a href="#" class="text-secondary" :title="$gettext('Mute')" @click.prevent="toggleMute">
                    <icon icon="volume_mute"></icon>
                </a>
            </div>
            <div class="radio-control-volume-slider">
                <input type="range" :title="$gettext('Volume')" class="custom-range" min="0" max="100" step="1"
                       :disabled="isMuted" v-model.number="volume">
            </div>
            <div class="radio-control-max-volume-button">
                <a href="#" class="text-secondary" :title="$gettext('Full Volume')" @click.prevent="fullVolume">
                    <icon icon="volume_up"></icon>
                </a>
            </div>
        </div>
    </div>
</template>

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

<script>
import {nowPlayingProps} from '~/components/Common/NowPlaying.js';

export const radioPlayerProps = {
    ...nowPlayingProps,
    showHls: {
        type: Boolean,
        default: true
    },
    hlsIsDefault: {
        type: Boolean,
        default: true
    },
    showAlbumArt: {
        type: Boolean,
        default: true
    },
    autoplay: {
        type: Boolean,
        default: false
    }
};
</script>

<script setup>
import AudioPlayer from '~/components/Common/AudioPlayer';
import Icon from '~/components/Common/Icon';
import PlayButton from "~/components/Common/PlayButton";
import {computed, onMounted, ref, shallowRef, watch} from "vue";
import {useIntervalFn, useMounted, useStorage} from "@vueuse/core";
import formatTime from "~/functions/formatTime";
import {useTranslate} from "~/vendor/gettext";
import useNowPlaying from "~/components/Common/NowPlaying.js";

const props = defineProps({
    ...radioPlayerProps
});

const emit = defineEmits(['np_updated']);

const np = useNowPlaying(props);
const np_elapsed = ref(0);
const current_stream = shallowRef({
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
    let all_streams = [];
    let $np = np.value;

    if (enable_hls.value) {
        all_streams.push({
            'name': $gettext('HLS'),
            'url': $np.station.hls_url,
            'hls': true,
        });
    }

    $np.station.mounts.forEach(function (mount) {
        all_streams.push({
            'name': mount.name,
            'url': mount.url,
            'hls': false,
        });
    });
    $np.station.remotes.forEach(function (remote) {
        all_streams.push({
            'name': remote.name,
            'url': remote.url,
            'hls': false,
        });
    });

    return all_streams;
});

const time_total = computed(() => {
    let $np = np.value;
    return $np?.now_playing?.duration ?? 0;
});

const time_percent = computed(() => {
    let $np_elapsed = np_elapsed.value;
    let $time_total = time_total.value;

    if (!$time_total) {
        return 0;
    }
    if ($np_elapsed > $time_total) {
        return 100;
    }

    return ($np_elapsed / $time_total) * 100;
});

const time_display_played = computed(() => {
    let $np_elapsed = np_elapsed.value;
    let $time_total = time_total.value;

    if (!$time_total) {
        return null;
    }

    if ($np_elapsed > $time_total) {
        $np_elapsed = $time_total;
    }

    return formatTime($np_elapsed);
});

const time_display_total = computed(() => {
    let $time_total = time_total.value;
    return ($time_total) ? formatTime($time_total) : null;
});

const isMounted = useMounted();
const player = ref(); // Template ref

const volume = useStorage('player_volume', 55);
const isMuted = useStorage('player_is_muted', false);

const toggleMute = () => {
    isMuted.value = !isMuted.value;
}

const fullVolume = () => {
    volume.value = 100;
};

const switchStream = (new_stream) => {
    current_stream.value = new_stream;
    player.value.toggle(new_stream.url, true, new_stream.hls);
};

onMounted(() => {
    useIntervalFn(
        () => {
            let $np = np.value;

            let current_time = Math.floor(Date.now() / 1000);
            let $np_elapsed = current_time - $np.now_playing.played_at;

            if ($np_elapsed < 0) {
                $np_elapsed = 0;
            } else if ($np_elapsed >= $np.now_playing.duration) {
                $np_elapsed = $np.now_playing.duration;
            }

            np_elapsed.value = $np_elapsed;
        },
        1000
    );

    if (props.autoplay) {
        switchStream(current_stream.value);
    }
});

const onNowPlayingUpdated = (np_new) => {
    emit('np_updated', np_new);

    // Set a "default" current stream if none exists.
    let $streams = streams.value;
    let $current_stream = current_stream.value;

    if ($current_stream.url === '' && $streams.length > 0) {
        if (props.hlsIsDefault && enable_hls.value) {
            current_stream.value = $streams[0];
        } else {
            $current_stream = null;

            if (np_new.station.listen_url !== '') {
                $streams.forEach(function (stream) {
                    if (stream.url === np_new.station.listen_url) {
                        $current_stream = stream;
                    }
                });
            }

            if ($current_stream === null) {
                $current_stream = $streams[0];
            }

            current_stream.value = $current_stream;
        }
    }
};

watch(np, onNowPlayingUpdated, {immediate: true});
</script>
