import {ref} from "vue";
import createRequiredInjectionState from "~/functions/createRequiredInjectionState.ts";

export const [useProvideMixer, useInjectMixer] = createRequiredInjectionState(
    (initialValue: number) => {
        const mixer = ref<number>(initialValue);

        return { mixer };
    }
);
