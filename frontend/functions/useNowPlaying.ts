import NowPlaying from "~/entities/NowPlaying";
import {computed, EffectScope, effectScope, isRef, MaybeRef, ref, shallowRef, watch} from "vue";
import {useDocumentVisibility, useEventSource, useIntervalFn} from "@vueuse/core";
import {ApiNowPlaying, ApiNowPlayingVueProps} from "~/entities/ApiInterfaces.ts";
import {getApiUrl} from "~/router.ts";
import {useAxios} from "~/vendor/axios.ts";
import formatTime from "~/functions/formatTime.ts";
import {isUndefined, omitBy} from "es-toolkit/compat";

interface SsePayload {
    data: {
        current_time?: number,
        np: ApiNowPlaying
    }
}

export default function useNowPlaying(
    props: MaybeRef<ApiNowPlayingVueProps>
) {
    const np = shallowRef<ApiNowPlaying>(NowPlaying);
    const npTimestamp = ref<number>(0);

    const currentTime = ref<number>(Math.floor(Date.now() / 1000));
    const currentTrackDuration = ref<number>(0);
    const currentTrackElapsed = ref<number>(0);

    const setNowPlaying = (np_new: ApiNowPlaying) => {
        if (!np_new.now_playing) {
            return;
        }

        np.value = np_new;
        npTimestamp.value = Date.now();

        currentTrackDuration.value = np_new.now_playing.duration ?? 0;

        // Update the browser metadata for browsers that support it (i.e. Mobile Chrome)
        if ('mediaSession' in navigator) {
            navigator.mediaSession.metadata = new MediaMetadata(omitBy({
                title: np_new.now_playing.song?.title ?? undefined,
                artist: np_new.now_playing.song?.artist ?? undefined,
                artwork: [
                    {src: np_new.now_playing.song?.art ?? undefined}
                ]
            }, isUndefined));

            const setPositionState = (duration: number, position: number): void => {
                if (position <= duration) {
                    navigator.mediaSession.setPositionState({
                        duration,
                        position,
                    });
                }
            };

            setPositionState(np_new.now_playing.duration ?? 0, np_new.now_playing.elapsed ?? 0);

            navigator.mediaSession.setActionHandler("seekto", () => {
                if (!np_new.now_playing) {
                    return;
                }

                setPositionState(np_new.now_playing.duration ?? 0, np_new.now_playing.elapsed ?? 0);
            });
        }

        document.dispatchEvent(new CustomEvent("now-playing", {
            detail: np_new
        }));
    }

    let scope: EffectScope | null = null;

    const initNowPlaying = (settings: ApiNowPlayingVueProps) => {
        if (scope !== null) {
            scope.stop(false);
        }

        scope = effectScope();
        scope.run(() => {
            const {stationShortName, useSse, useStatic} = settings;

            if (useSse) {
                const sseBaseUri = getApiUrl('/live/nowplaying/sse');
                const sseUriParams = new URLSearchParams({
                    "cf_connect": JSON.stringify({
                        "subs": {
                            [`station:${stationShortName}`]: {
                                "recover": true
                            },
                        }
                    }),
                });
                const sseUri = sseBaseUri.value + '?' + sseUriParams.toString();

                const handleSseData = (ssePayload: SsePayload, useTime: boolean = true) => {
                    const jsonData = ssePayload.data;

                    if (useTime && jsonData.current_time) {
                        currentTime.value = jsonData.current_time;
                    }

                    if (npTimestamp.value === 0) {
                        setNowPlaying(jsonData.np);
                    } else {
                        // SSE events often dispatch *too quickly* relative to the delays involved in
                        // Liquidsoap and Icecast, so we delay these changes from showing up to better
                        // approximate when listeners will really hear the track change.
                        setTimeout(() => {
                            setNowPlaying(jsonData.np);
                        }, 3000);
                    }
                }

                const {data, close: closeSse, open: openSse} = useEventSource(sseUri);
                watch(data, (dataRaw: string | null) => {
                    if (!dataRaw) {
                        return;
                    }

                    const jsonData = JSON.parse(dataRaw);

                    if ('connect' in jsonData) {
                        const connectData = jsonData.connect;

                        // New Centrifugo time format
                        if ('time' in connectData) {
                            currentTime.value = Math.floor(connectData.time / 1000);
                        }

                        // New Centrifugo cached NowPlaying initial push.
                        for (const subName in connectData.subs) {
                            const sub = connectData.subs[subName];
                            if ('publications' in sub && sub.publications.length > 0) {
                                sub.publications.forEach((initialRow: SsePayload) => handleSseData(initialRow, false));
                            }
                        }
                    } else if ('pub' in jsonData) {
                        handleSseData(jsonData.pub);
                    }
                });

                const visibility = useDocumentVisibility();
                watch(
                    visibility,
                    (newValue: string, oldValue: string) => {
                        if (newValue === 'hidden') {
                            console.log('Window hidden; suspending NP data...');
                            closeSse();
                        } else if (newValue === 'visible' && oldValue === 'hidden') {
                            console.log('Window shown; resuming NP data...');
                            openSse();
                        }
                    }
                )
            } else {
                const nowPlayingUri = useStatic
                    ? getApiUrl(`/nowplaying_static/${stationShortName}.json`)
                    : getApiUrl(`/nowplaying/${stationShortName}`);

                const timeUri = getApiUrl('/time');
                const {axiosSilent} = useAxios();

                const axiosNoCacheConfig = {
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache',
                        'Expires': '0',
                    }
                };

                const {
                    pause: pauseNp,
                    resume: resumeNp,
                } = useIntervalFn(
                    () => void (async () => {
                        const {data} = await axiosSilent.get<ApiNowPlaying>(nowPlayingUri.value, axiosNoCacheConfig);
                        setNowPlaying(data);
                    })(),
                    30000,
                    {
                        immediateCallback: true
                    }
                );

                const {
                    pause: pauseTimer,
                    resume: resumeTimer
                } = useIntervalFn(
                    () => void (async () => {
                        const {data} = await axiosSilent.get(timeUri.value, axiosNoCacheConfig);
                        currentTime.value = data.timestamp;
                    })(),
                    600000,
                    {
                        immediateCallback: true
                    }
                );

                const visibility = useDocumentVisibility();
                watch(
                    visibility,
                    (newValue: string, oldValue: string) => {
                        if (newValue === 'hidden') {
                            console.log('Window hidden; suspending NP data...');
                            pauseNp();
                            pauseTimer();
                        } else if (newValue === 'visible' && oldValue === 'hidden') {
                            console.log('Window shown; resuming NP data...');
                            resumeNp();
                            resumeTimer();
                        }
                    }
                )
            }

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
                1000,
                {
                    immediateCallback: true
                }
            );
        });
    }

    if (isRef(props)) {
        watch(props, (newProps) => {
            initNowPlaying(newProps);
        }, {
            immediate: true
        });
    } else {
        initNowPlaying(props);
    }

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
