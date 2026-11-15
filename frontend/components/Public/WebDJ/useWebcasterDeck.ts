import { useEventListener, useRafFn } from "@vueuse/core";

import {
    Deck,
    formatTime,
    Side,
    TagReader,
    Track,
} from "@webcast.js/webcaster";
import { computed, ref, shallowRef, watch } from "vue";
import {
    silentLevels,
    useInjectWebcasterMixer,
    WebcasterLevels,
    WebcasterMetadata,
} from "~/components/Public/WebDJ/useWebcasterMixer.ts";
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
        const { mixer, cued, currentMetadata, sendMetadata } =
            useInjectWebcasterMixer();

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

        const levels = shallowRef<WebcasterLevels>(silentLevels);

        useRafFn(
            () => {
                levels.value = deck.levels() ?? silentLevels;
            },
            {
                immediate: true,
                fpsLimit: () => 30,
            },
        );

        const duration = ref<number>(0);
        const position = ref<number>(0);

        useRafFn(
            () => {
                duration.value = deck.duration;
                position.value = deck.position;
            },
            {
                immediate: true,
                fpsLimit: () => 5,
            },
        );

        const playThrough = ref<boolean>(true);

        watch(playThrough, (newPlayThrough) => {
            deck.playThrough = newPlayThrough;
        });

        const loop = ref<boolean>(false);

        watch(loop, (newLoop) => {
            deck.loop = newLoop;
        });

        let sentMetadata: WebcasterMetadata | null = null;

        const sendTrackMetadata = (track: Track) => {
            sentMetadata = { artist: track.artist, title: track.title };
            sendMetadata(sentMetadata);
        };

        const isPlaying = shallowRef<boolean>(false);
        const isLoaded = shallowRef<boolean>(false);

        useEventListener(deck, "change", () => {
            if (deck.loaded && !isLoaded.value) {
                const track = deck.tracks[deck.index];
                if (track) {
                    sendTrackMetadata(track);
                }
            }

            isPlaying.value = deck.playing;
            isLoaded.value = deck.loaded;
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

        useEventListener(deck, "tracks", () => {
            const track = deck.tracks[deck.index];

            if (
                !deck.loaded ||
                !track ||
                currentMetadata.value !== sentMetadata
            ) {
                return;
            }

            if (
                track.title !== sentMetadata.title ||
                track.artist !== sentMetadata.artist
            ) {
                sendTrackMetadata(track);
            }
        });

        const playFile = (index: number) => {
            deck.play(index);
        };

        return {
            deck,
            gain,
            levels,
            playThrough,
            loop,
            isPlaying,
            isLoaded,
            duration,
            position,
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
