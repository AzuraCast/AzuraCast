import { useEventListener } from "@vueuse/core";
import {
    type Cueable,
    Mixer,
    StreamOptions,
    StreamState,
} from "@webcast.js/webcaster";
import { computed, shallowRef } from "vue";
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

export const [useProvideWebcasterMixer, useInjectWebcasterMixer] =
    createRequiredInjectionState(() => {
        const mixer = new Mixer();

        const state = shallowRef<StreamState>();
        const message = shallowRef<string>();
        const cued = shallowRef<Cueable | null>(null);

        const isIdle = computed(() => state.value !== "idle");

        useEventListener(mixer, "change", () => {
            cued.value = mixer.cued;
            state.value = mixer.state;
        });

        useEventListener(mixer, "status", () => {
            message.value = mixer.message;
        });

        const stopStream = () => {
            if (!isIdle.value) {
                mixer.stopStream();
            }
        };

        const startStream = (options: StreamOptions) => {
            stopStream();

            mixer.startStream(options);
        };

        const sendMetadata = (metadata: Record<string, string>) => {
            mixer.sendMetadata(metadata);
        };

        const setCrossfade = (position: number) => {
            mixer.setCrossfade(position);
        };

        const toggleGlobalCue = () => {
            mixer.toggleCue(mixer);
        };

        const isGlobalCued = computed(() => cued.value === mixer);

        return {
            mixer,
            state,
            message,
            cued,
            isIdle,
            stopStream,
            startStream,
            sendMetadata,
            setCrossfade,
            toggleGlobalCue,
            isGlobalCued,
        };
    });
