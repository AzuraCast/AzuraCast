import NowPlaying from '~/entities/NowPlaying';
import {computed, onMounted, Ref, ref, ShallowRef, shallowRef, watch} from "vue";
import {useEventSource, useIntervalFn} from "@vueuse/core";
import {ApiNowPlaying} from "~/entities/ApiInterfaces.ts";
import {getApiUrl} from "~/router.ts";
import {useAxios} from "~/vendor/axios.ts";
import formatTime from "~/functions/formatTime.ts";

export const nowPlayingProps = {
    stationShortName: {
        type: String,
        required: true,
    },
    useStatic: {
        type: Boolean,
        required: false,
        default: false,
    },
    useSse: {
        type: Boolean,
        required: false,
        default: false
    },
};

interface NowPlayingSSETime {
    timestamp: number
}

interface NowPlayingSSEResponse {
    type: string,
    payload: NowPlayingSSETime | ApiNowPlaying
}

export default function useNowPlaying(props) {
    const np: ShallowRef<ApiNowPlaying> = shallowRef(NowPlaying);
    const npUpdated: Ref<number> = ref(0);

    const currentTime: Ref<number> = ref(Math.floor(Date.now() / 1000));
    const currentTrackDuration: Ref<number> = ref(0);
    const currentTrackElapsed: Ref<number> = ref(0);

    const setNowPlaying = (np_new: ApiNowPlaying) => {
        np.value = np_new;
        npUpdated.value = currentTime.value;

        currentTrackDuration.value = np_new.now_playing.duration ?? 0;

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

    if (props.useSse) {
        const sseUri = getApiUrl(`/live/nowplaying/${props.stationShortName}`);

        const {data} = useEventSource(sseUri.value);
        watch(data, (dataRaw: string) => {
            const jsonData: NowPlayingSSEResponse = JSON.parse(dataRaw);

            if (jsonData.type === 'time') {
                currentTime.value = jsonData.payload.timestamp;
            } else if (jsonData.type === 'nowplaying') {
                if (npUpdated.value === 0) {
                    setNowPlaying(jsonData.payload);
                } else {
                    setTimeout(() => {
                        setNowPlaying(jsonData.payload);
                    }, 3000);
                }
            }
        });
    } else {
        const nowPlayingUri = props.useStatic
            ? getApiUrl(`/nowplaying_static/${props.stationShortName}`)
            : getApiUrl(`/nowplaying/${props.stationShortName}`);

        const timeUri = getApiUrl('/time');

        const {axios} = useAxios();
        const checkNowPlaying = () => {
            axios.get(nowPlayingUri.value, {
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
            axios.get(timeUri.value, {
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
            checkTime();
            checkNowPlaying();
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
        if (!currentTrackDuration.value) {
            return 0;
        }
        if (currentTrackElapsed.value > currentTrackDuration.value) {
            return 100;
        }

        return (currentTrackElapsed.value / currentTrackDuration.value) * 100;
    });

    const currentTrackDurationDisplay = computed(() => {
        return (currentTrackDuration.value) ? formatTime(currentTrackDuration.value) : null;
    });

    const currentTrackElapsedDisplay = computed(() => {
        if (!currentTrackDuration.value) {
            return null;
        }

        return (currentTrackElapsed.value <= currentTrackDuration.value)
            ? formatTime(currentTrackElapsed.value)
            : currentTrackDurationDisplay.value;
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
