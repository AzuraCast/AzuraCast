import { useDevicesList, useEventListener, useRafFn } from "@vueuse/core";

import { Microphone } from "@webcast.js/webcaster";
import { computed, ref, shallowRef, watch } from "vue";
import { useNotify } from "~/components/Common/Toasts/useNotify.ts";
import { useInjectWebcasterMixer } from "~/components/Public/WebDJ/useWebcasterMixer.ts";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";
import { useTranslate } from "~/vendor/gettext";

export const [useProvideWebcasterMicrophone, useInjectWebcasterMicrophone] =
    createRequiredInjectionState(() => {
        const { mixer, cued } = useInjectWebcasterMixer();

        const { notifyError } = useNotify();
        const { $gettext } = useTranslate();

        const microphone = new Microphone(mixer);

        const gain = shallowRef<number>(100);

        watch(gain, (newGain) => {
            microphone.setGain(newGain / 100);
        });

        const leftLevel = shallowRef<number>(0);
        const rightLevel = shallowRef<number>(0);

        useRafFn(
            () => {
                const levels = microphone.levels();

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

        const isActive = ref<boolean>(false);

        useEventListener(microphone, "change", () => {
            isActive.value = microphone.active;
        });

        const toggleCue = () => {
            mixer.toggleCue(microphone);
        };

        const isCued = computed(() => cued.value === microphone);

        const stop = () => {
            microphone.stop();
        };

        const { audioInputs } = useDevicesList({
            requestPermissions: true,
            constraints: { audio: true, video: false },
        });

        const device = ref<string | null>(null);

        watch(audioInputs, (inputs) => {
            if (device.value === null) {
                device.value = inputs[0]?.deviceId;
            }
        });

        const start = () => {
            microphone
                .start({
                    deviceId: device.value || undefined,
                    lineIn: false,
                })
                .catch(() => {
                    notifyError(
                        $gettext("The microphone could not be enabled."),
                    );
                });
        };

        watch(device, () => {
            if (isActive.value) {
                start();
            }
        });

        return {
            microphone,
            gain,
            leftLevel,
            rightLevel,
            isActive,
            toggleCue,
            isCued,
            audioInputs,
            device,
            stop,
            start,
        };
    });
