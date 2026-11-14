import { useEventListener, useRafFn } from "@vueuse/core";

import { Deck, formatTime, Side, TagReader } from "@webcast.js/webcaster";
import { computed, ref, shallowRef, watch } from "vue";
import { useInjectWebcasterMixer } from "~/components/Public/WebDJ/useWebcasterMixer.ts";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";

export type TaglibData = {
    metadata?: { title?: string; artist?: string };
    audio?: { length?: number };
};

declare global {
    interface File {
        readTaglibMetadata?: (callback: (data: TaglibData) => void) => void;
    }
}

export type FileRow = {
    index: number;
    isCurrent: boolean;
    title: string;
    artist: string;
    duration: string;
};

export const [useProvideWebcasterDeck, useInjectWebcasterDeck] =
    createRequiredInjectionState((side: Side) => {
        const { mixer, cued, sendMetadata } = useInjectWebcasterMixer();

        // taglib.js never calls back on files it cannot parse: those tracks keep their file name.
        const readTags: TagReader = (file) =>
            new Promise((resolve) =>
                file.readTaglibMetadata?.(({ metadata, audio }) =>
                    resolve({
                        title: metadata?.title,
                        artist: metadata?.artist,
                        duration: audio?.length,
                    }),
                ),
            );

        const deck = new Deck(mixer, side, readTags);

        const gain = shallowRef<number>(100);

        watch(gain, (newGain) => {
            deck.setGain(newGain / 100);
        });

        const leftLevel = shallowRef<number>(0);
        const rightLevel = shallowRef<number>(0);

        useRafFn(
            () => {
                const levels = deck.levels();

                if (levels) {
                    leftLevel.value = levels[0] ?? 0;
                    rightLevel.value = levels[1] ?? 0;
                }
            },
            {
                immediate: true,
                fpsLimit: () => 30,
            },
        );

        const duration = ref<number>(0);
        const position = ref<number>(0);
        const time = ref<string>("--:-- / --:--");

        useRafFn(
            () => {
                duration.value = deck.duration;
                position.value = deck.position;

                time.value = deck.loaded
                    ? `${formatTime(deck.position)} / ${formatTime(deck.duration)}`
                    : "--:-- / --:--";
            },
            {
                immediate: true,
                fpsLimit: () => 5,
            },
        );

        const playThrough = ref<boolean>(false);

        watch(playThrough, (newPlayThrough) => {
            deck.playThrough = newPlayThrough;
        });

        const loop = ref<boolean>(false);

        watch(loop, (newLoop) => {
            deck.loop = newLoop;
        });

        const isPlaying = shallowRef<boolean>(false);
        const isLoaded = shallowRef<boolean>(false);
        const currentIndex = shallowRef<number>(-1);

        useEventListener(deck, "change", () => {
            isPlaying.value = deck.playing;
            isLoaded.value = deck.loaded;
            currentIndex.value = deck.index;
        });

        const toggleCue = () => {
            mixer.toggleCue(deck);
        };

        const isCued = computed(() => cued.value === deck);

        const togglePause = () => {
            deck.togglePause();
        };

        const previous = () => {
            deck.skip({ backward: true });
        };

        const next = () => {
            deck.skip();
        };

        const stop = () => {
            deck.stop();
        };

        const seek = (value: number) => {
            deck.seek(value);
        };

        const addFiles = (files: FileList) => {
            deck.add(files);
        };

        const files = shallowRef<FileRow[]>([]);

        useEventListener(deck, "tracks", () => {
            files.value = deck.tracks.map((track, index) => {
                return {
                    index: index,
                    isCurrent: index === deck.index,
                    title: track.title,
                    artist: track.artist,
                    duration: formatTime(track.duration),
                };
            });
        });

        // Detect track changes.
        watch(currentIndex, (newIndex, oldIndex) => {
            if (newIndex === oldIndex || newIndex === -1) {
                return;
            }

            const newFile = files.value.find((row) => row.index === newIndex);
            if (newFile) {
                sendMetadata({
                    artist: newFile.artist,
                    title: newFile.title,
                });
            }
        });

        const playFile = (index: number) => {
            deck.play(index);
        };

        return {
            deck,
            gain,
            leftLevel,
            rightLevel,
            playThrough,
            loop,
            isPlaying,
            isLoaded,
            duration,
            position,
            time,
            toggleCue,
            isCued,
            togglePause,
            previous,
            next,
            stop,
            seek,
            addFiles,
            files,
            playFile,
        };
    });
