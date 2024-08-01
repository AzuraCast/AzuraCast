import {RemovableRef} from "@vueuse/core";
import useOptionalStorage from "~/functions/useOptionalStorage.ts";

export const DEFAULT_VOLUME: number = 55;

export default function usePlayerVolume(): RemovableRef<number> {
    return useOptionalStorage('player_volume', DEFAULT_VOLUME, {
        listenToStorageChanges: false
    });
}
