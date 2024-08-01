import { createInjectionState } from "@vueuse/core";
import { ref } from "vue";

export const [useProvideMixer, useInjectMixer] = createInjectionState(
    (initialValue: number) => {
        const mixer = ref<number>(initialValue);

        return { mixer };
    }
);
