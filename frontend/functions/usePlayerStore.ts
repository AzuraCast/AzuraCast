import {createInjectionState} from '@vueuse/shared';
import {Ref, ref, shallowRef} from "vue";
import getUrlWithoutQuery from "~/functions/getUrlWithoutQuery.ts";

export interface StreamDescriptor {
    url: string | null,
    isHls: boolean,
    isStream: boolean
}

const [useProvidePlayerStore, usePlayerStore] = createInjectionState(
    (initialChannel: string) => {
        const channel: Ref<string> = ref(initialChannel);

        const isPlaying: Ref<boolean> = ref(false);

        const current: Ref<StreamDescriptor> = shallowRef({
            url: null,
            isHls: false,
            isStream: false
        });

        const toggle = (payload: StreamDescriptor): void => {
            const currentUrl = getUrlWithoutQuery(current.value.url);
            const newUrl = getUrlWithoutQuery(payload.url);

            if (currentUrl === newUrl) {
                current.value = {
                    url: null,
                    isHls: false,
                    isStream: false
                };
            } else {
                current.value = payload;
            }
        };

        const stop = (): void => {
            toggle({
                url: null,
                isStream: true,
                isHls: false,
            });
        };

        return {
            channel,
            isPlaying,
            current,
            toggle,
            stop
        };
    }
);

export {useProvidePlayerStore, usePlayerStore};
