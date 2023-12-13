import NowPlaying from '~/entities/NowPlaying';
import {computed, onMounted, Ref, ref, ShallowRef, shallowRef, watch} from "vue";
import {useEventSource, useIntervalFn} from "@vueuse/core";
import {ApiNowPlaying} from "~/entities/ApiInterfaces.ts";
import {getApiUrl} from "~/router.ts";
import {useAxios} from "~/vendor/axios.ts";
import formatTime from "~/functions/formatTime.ts";
import {has} from "lodash";

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

interface SSETimePayload {
    timestamp: number
}

interface SSENowPlayingPayload {
    station: string,
    np: ApiNowPlaying,
    triggers: string[] | null
}

interface SSEResponse {
    type: string,
    payload: SSETimePayload | SSENowPlayingPayload
}

export default function useNowPlaying(props) {
    const np: ShallowRef<ApiNowPlaying> = shallowRef(NowPlaying);

    const currentTime: Ref<number> = ref(Math.floor(Date.now() / 1000));
    const currentTrackDuration: Ref<number> = ref(0);
    const currentTrackElapsed: Ref<number> = ref(0);

    const setNowPlaying = (np_new: ApiNowPlaying) => {
        np.value = np_new;

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

    const nowPlayingUri = props.useStatic
        ? getApiUrl(`/nowplaying_static/${props.stationShortName}.json`)
        : getApiUrl(`/nowplaying/${props.stationShortName}`);

    const timeUri = getApiUrl('/time');
    const {axiosSilent} = useAxios();

    const axiosNoCacheConfig = {
        headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache',
            'Expires': '0',
        }
    };

    if (props.useSse) {
        const sseBaseUri = getApiUrl('/live/nowplaying/sse');
        const sseUriParams = new URLSearchParams({
            "cf_connect": JSON.stringify({
                "subs": {
                    [`station:${props.stationShortName}`]: {},
                    "global:time": {},
                }
            }),
        });
        const sseUri = sseBaseUri.value + '?' + sseUriParams.toString();

        // Make an initial AJAX request before SSE takes over.
        axiosSilent.get(nowPlayingUri.value, axiosNoCacheConfig).then((response) => {
            setNowPlaying(response.data);
        });

        axiosSilent.get(timeUri.value, axiosNoCacheConfig).then((response) => {
            currentTime.value = response.data.timestamp;
        });

        // Subsequent events come from SSE.
        const {data} = useEventSource(sseUri);
        watch(data, (dataRaw: string) => {
            const jsonData: SSEResponse = JSON.parse(dataRaw);
            const jsonDataNp = jsonData?.pub?.data ?? {};

            if (has(jsonDataNp, 'np')) {
                setTimeout(() => {
                    setNowPlaying(jsonDataNp.np);
                }, 3000);
            } else if (has(jsonDataNp, 'time')) {
                currentTime.value = jsonDataNp.time;
            }
        });
    } else {
        const checkNowPlaying = () => {
            axiosSilent.get(nowPlayingUri.value, axiosNoCacheConfig).then((response) => {
                setNowPlaying(response.data);

                setTimeout(checkNowPlaying, (!document.hidden) ? 15000 : 30000);
            }).catch(() => {
                setTimeout(checkNowPlaying, (!document.hidden) ? 30000 : 120000);
            });
        };

        const checkTime = () => {
            axiosSilent.get(timeUri.value, axiosNoCacheConfig).then((response) => {
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
