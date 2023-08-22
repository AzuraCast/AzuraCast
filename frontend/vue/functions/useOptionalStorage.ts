import {MaybeRefOrGetter, ref, shallowRef} from "vue";
import {RemovableRef, useLocalStorage, UseStorageOptions} from "@vueuse/core";
import storageAvailable from "~/functions/storageAvailable";

export default function useOptionalStorage<T extends (string | number | boolean | object | null)>(
    key: string,
    defaults: MaybeRefOrGetter<T>,
    options: UseStorageOptions<T>
): RemovableRef<T> {
    if (storageAvailable('localStorage')) {
        return useLocalStorage(key, defaults, options);
    }

    const {shallow} = options;
    return (shallow ? shallowRef : ref)(defaults) as RemovableRef<T>;
}
