import {useSupported} from "@vueuse/core";
import {ComputedRef} from "vue";

export default function useShowVolume(): ComputedRef<boolean> {
    return useSupported(() => {
        const audio = new Audio();
        audio.volume = 0.5;
        return audio.volume !== 1;
    });
}
