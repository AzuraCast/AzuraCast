import { useEventListener } from "@vueuse/core";
import {
    type Cueable,
    Mixer,
    StreamOptions,
    StreamState,
} from "@webcast.js/webcaster";
import { computed, shallowRef, watch } from "vue";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";
import { useTranslate } from "~/vendor/gettext";

export type WebcasterMetadata = {
    artist: string;
    title: string;
};

export type WebcasterLevels = readonly [left: number, right: number];

// Webcaster floors levels at -60 dBFS
export const silentLevels: WebcasterLevels = [-60, -60];

export const [useProvideWebcasterMixer, useInjectWebcasterMixer] =
    createRequiredInjectionState(() => {
        const mixer = new Mixer();

        const state = shallowRef<StreamState>("idle");
        const cued = shallowRef<Cueable | null>(null);

        const isIdle = computed(() => state.value === "idle");

        useEventListener(mixer, "change", () => {
            cued.value = mixer.cued;
            state.value = mixer.state;
        });

        const { notifySuccess, notifyError } = useNotify();
        const { $gettext } = useTranslate();

        let isStopping = false;
        let previousState: StreamState = "idle";

        useEventListener(mixer, "status", () => {
            switch (mixer.state) {
                case "live":
                    notifySuccess($gettext("Web DJ connected!"));
                    break;

                case "idle":
                    if (!isStopping) {
                        notifyError(
                            previousState === "live"
                                ? $gettext("The Web DJ was disconnected.")
                                : $gettext(
                                      "The Web DJ could not start streaming.",
                                  ),
                        );
                    }

                    isStopping = false;
                    break;
            }

            previousState = mixer.state;
        });

        const stopStream = () => {
            if (!isIdle.value) {
                isStopping = true;
                mixer.stopStream();
            }
        };

        const startStream = (options: StreamOptions) => {
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

        watch(state, (newState, oldState) => {
            if (newState === "live" && newState !== oldState) {
                // Stream just went live; send metadata.
                sendMetadata(currentMetadata.value);
            }
        });

        return {
            mixer,
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
