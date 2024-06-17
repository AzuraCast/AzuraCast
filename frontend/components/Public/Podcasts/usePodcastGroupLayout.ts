import {ref, Ref} from "vue";
import {createInjectionState} from "@vueuse/shared";

const [useProvidePodcastGroupLayout, usePodcastGroupLayout] = createInjectionState(
    (initialGroupLayout: string) => {
        const groupLayout: Ref<string> = ref(initialGroupLayout);

        return {
            groupLayout
        };
    }
);

export {useProvidePodcastGroupLayout, usePodcastGroupLayout};
