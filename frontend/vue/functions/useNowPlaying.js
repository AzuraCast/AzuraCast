import NowPlaying from '~/components/Entity/NowPlaying';
import {computed, onMounted, ref, shallowRef, watch} from "vue";
import {useEventSource, useIntervalFn} from "@vueuse/core";
import {useAxios} from "~/vendor/axios";
import {has} from "lodash";
import formatTime from "~/functions/formatTime";

export const nowPlayingProps = {
    nowPlayingUri: {
        type: String,
        required: true
    },
    useSse: {
        type: Boolean,
        required: false,
        default: false
    },
    sseUri: {
        type: String,
        required: false,
        default: null
    },
    initialNowPlaying: {
        type: Object,
        default() {
            return NowPlaying;
        }
    },
    timeUri: {
        type: String,
        required: true
    },
};

export default function useNowPlaying(props) {
    const np = shallowRef(props.initialNowPlaying);

    const currentTime = ref(Math.floor(Date.now() / 1000));
    const currentTrackDuration = ref(0);
    const currentTrackElapsed = ref(0);

    const setNowPlaying = (np_new) => {
        np.value = np_new;

        currentTrackDuration.value = np_new?.now_playing?.duration ?? 0;

        // Update the browser metadata for browsers that support it (i.e. Mobile Chrome)
        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata({
                title: np_new.now_playing.song.title,
                artist: np_new.now_playing.song.artist,
                artwork: [
                    {src: np_new.now_playing.song.art}
                ]
            });
        }

        document.dispatchEvent(new CustomEvent("now-playing", {
            detail: np_new
        }));
    }

    // Trigger initial NP set.
    setNowPlaying(np.value);

    if (props.useSse) {
        const {data} = useEventSource(props.sseUri);
        watch(data, (data_raw) => {
            const json_data = JSON.parse(data_raw);
            const json_data_np = json_data?.pub?.data ?? {};

            if (has(json_data_np, 'np')) {
                setTimeout(() => {
                    setNowPlaying(json_data_np.np);
                }, 3000);
            } else if (has(json_data_np, 'time')) {
                currentTime.value = json_data_np.time;
            }
        });
    } else {
        const {axios} = useAxios();
        const checkNowPlaying = () => {
            axios.get(props.nowPlayingUri, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache',
                    'Expires': '0',
                }
            }).then((response) => {
                setNowPlaying(response.data);

                setTimeout(checkNowPlaying, (!document.hidden) ? 15000 : 30000);
            }).catch(() => {
                setTimeout(checkNowPlaying, (!document.hidden) ? 30000 : 120000);
            });
        };

        const checkTime = () => {
            axios.get(props.timeUri, {
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache',
                    'Expires': '0',
                }
            }).then((response) => {
                currentTime.value = response.data.timestamp;
            }).finally(() => {
                setTimeout(checkTime, (!document.hidden) ? 300000 : 600000);
            });
        };

        onMounted(() => {
            setTimeout(checkTime, 5000);
            setTimeout(checkNowPlaying, 5000);
        });
    }

    onMounted(() => {
        useIntervalFn(
            () => {
                const currentTrackPlayedAt = np.value?.now_playing?.played_at ?? 0;
                let elapsed = currentTime.value - currentTrackPlayedAt;

                if (elapsed < 0) {
                    elapsed = 0;
                } else if (elapsed >= currentTrackDuration.value) {
                    elapsed = currentTrackDuration.value;
                }

                currentTrackElapsed.value = elapsed;
                currentTime.value = currentTime.value + 1;
            },
            1000
        );
    });

    const currentTrackPercent = computed(() => {
        const $currentTrackElapsed = currentTrackElapsed.value;
        const $currentTrackDuration = currentTrackDuration.value;

        if (!$currentTrackDuration) {
            return 0;
        }
        if ($currentTrackElapsed > $currentTrackDuration) {
            return 100;
        }

        return ($currentTrackElapsed / $currentTrackDuration) * 100;
    });

    const currentTrackDurationDisplay = computed(() => {
        const $currentTrackDuration = currentTrackDuration.value;
        return ($currentTrackDuration) ? formatTime($currentTrackDuration) : null;
    });

    const currentTrackElapsedDisplay = computed(() => {
        let $currentTrackElapsed = currentTrackElapsed.value;
        const $currentTrackDuration = currentTrackDuration.value;

        if (!$currentTrackDuration) {
            return null;
        }

        if ($currentTrackElapsed > $currentTrackDuration) {
            $currentTrackElapsed = $currentTrackDuration;
        }

        return formatTime($currentTrackElapsed);
    });
    
    return {
        np,
        currentTime,
        currentTrackDuration,
        currentTrackElapsed,
        currentTrackPercent,
        currentTrackDurationDisplay,
        currentTrackElapsedDisplay
    };
}
