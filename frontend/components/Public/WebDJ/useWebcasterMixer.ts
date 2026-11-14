import { useEventListener } from "@vueuse/core";
import {
    type Cueable,
    Mixer,
    StreamOptions,
    StreamState,
} from "@webcast.js/webcaster";
import { computed, shallowRef } from "vue";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import { FlashLevels } from "~/entities/ApiInterfaces.ts";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";

export type WebcasterMetadata = {
    artist: string;
    title: string;
};

export const [useProvideWebcasterMixer, useInjectWebcasterMixer] =
    createRequiredInjectionState(() => {
        const mixer = new Mixer();

        const state = shallowRef<StreamState>("idle");
        const message = shallowRef<string>();
        const cued = shallowRef<Cueable | null>(null);

        const isIdle = computed(() => state.value === "idle");

        useEventListener(mixer, "change", () => {
            cued.value = mixer.cued;
            state.value = mixer.state;
        });

        const { notify } = useNotify();

        useEventListener(mixer, "status", () => {
            notify(mixer.message, {
                variant: FlashLevels.Info,
            });

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

        const setCrossfade = (position: number) => {
            mixer.setCrossfade(position);
        };

        const toggleGlobalCue = () => {
            mixer.toggleCue(mixer);
        };

        const isGlobalCued = computed(() => cued.value === mixer);

        const currentMetadata = shallowRef<WebcasterMetadata>({
            artist: "",
            title: "",
        });

        const sendMetadata = (newMetadata: WebcasterMetadata) => {
            mixer.sendMetadata(newMetadata);
            currentMetadata.value = newMetadata;
        };

        return {
            mixer,
            state,
            message,
            cued,
            isIdle,
            stopStream,
            startStream,
            setCrossfade,
            toggleGlobalCue,
            isGlobalCued,
            currentMetadata,
            sendMetadata,
        };
    });
