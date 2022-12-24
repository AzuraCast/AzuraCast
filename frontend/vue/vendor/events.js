import {useEventBus} from "@vueuse/core";

export function useNotifyBus() {
    return useEventBus('notify');
}
