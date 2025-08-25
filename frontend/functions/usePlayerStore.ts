import {computed, ref, shallowRef} from "vue";
import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery.ts";
import {defineStore} from "pinia";
import useOptionalStorage from "~/functions/useOptionalStorage.ts";
import useShowVolume from "~/functions/useShowVolume.ts";
import formatTime from "~/functions/formatTime.ts";
import {throttle} from "lodash";

export enum StreamChannel {
    Global = 0,
    Modal = 1,
}

export type StreamDescriptor = {
    channel?: StreamChannel,
    url?: string | null,
    title?: string | null,
    isHls?: boolean,
    isStream?: boolean
};

export type FullStreamDescriptor = Required<StreamDescriptor>;

export const blankStreamDescriptor: FullStreamDescriptor = {
    channel: StreamChannel.Global,
    url: null,
    title: '',
    isHls: false,
    isStream: false
}

export type StreamPosition = {
    position: number,
    isSeek: boolean
}

export const DEFAULT_VOLUME: number = 55;

export const usePlayerStore = defineStore(
    'global-player',
    () => {
        const volume = useOptionalStorage<number>('player_volume', DEFAULT_VOLUME, {
            listenToStorageChanges: false
        });

        const showVolume = useShowVolume();

        const setVolume = (newVolume: number): void => {
            if (showVolume.value) {
                volume.value = newVolume;
            }
        }

        const isMuted = useOptionalStorage<boolean>('player_muted', false, {
            listenToStorageChanges: false
        });

        const toggleMute = (): void => {
            isMuted.value = !isMuted.value;
        }

        const isPlaying = ref<boolean>(false);

        const current = shallowRef<FullStreamDescriptor>({
            ...blankStreamDescriptor
        });

        const toggle = (payload: StreamDescriptor = {}): void => {
            const newStream: FullStreamDescriptor = {
                ...blankStreamDescriptor,
                ...payload
            };

            const currentUrl = getUrlWithoutQuery(current.value.url);
            const newUrl = getUrlWithoutQuery(newStream.url);

            if (currentUrl === newUrl) {
                current.value = {
                    ...blankStreamDescriptor
                };
            } else {
                current.value = newStream;
            }
        };

        const stop = (): void => {
            toggle();
        };

        const duration = ref<number>(0);
        const durationText = computed(() => formatTime(duration.value));

        const currentTime = ref<number>(0);
        const currentTimeText = computed(() => formatTime(currentTime.value));

        const progress = ref<StreamPosition>({
            position: 0,
            isSeek: false
        });

        const setPlayPosition = throttle(
            (newDuration: number, newCurrentTime: number): void => {
                duration.value = newDuration;
                currentTime.value = newCurrentTime;

                progress.value = {
                    position: (newDuration !== 0)
                        ? +((newCurrentTime / newDuration) * 100).toFixed(2)
                        : 0,
                    isSeek: false
                };
            },
            500
        );

        const setIsPlaying = (newIsPlaying: boolean): void => {
            isPlaying.value = newIsPlaying;

            if (!newIsPlaying) {
                setPlayPosition(0, 0);
            }
        }

        const seek = throttle(
            (newProgress: number): void => {
                progress.value = {
                    position: newProgress,
                    isSeek: true
                };
            },
            50
        );

        return {
            showVolume,
            volume,
            setVolume,
            isMuted,
            toggleMute,
            isPlaying,
            setIsPlaying,
            current,
            toggle,
            stop,
            duration,
            durationText,
            currentTime,
            currentTimeText,
            progress,
            setPlayPosition,
            seek,
        };
    },
);
