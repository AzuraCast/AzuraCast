import {createGlobalState} from "@vueuse/core";
import {ref} from "vue";

export function useMixerValue() {
    return createGlobalState(
        () => ref(0.5)
    );
}
