import {MaybeRefOrGetter, ref, shallowRef} from "vue";
import {RemovableRef, useLocalStorage, UseStorageOptions} from "@vueuse/core";

type StorageType = 'localStorage';

const storageAvailable = (type: StorageType): boolean => {
    try {
        const storage: Storage = window[type],
            x: string = '__storage_test__';
        storage.setItem(x, x);
        storage.removeItem(x);
        return true;
    } catch {
        return false;
    }
}

export default function useOptionalStorage<T extends (string | number | boolean | object | null)>(
    key: MaybeRefOrGetter<string>,
    defaults: MaybeRefOrGetter<T>,
    options?: UseStorageOptions<T>
): RemovableRef<T> {
    if (storageAvailable('localStorage')) {
        return useLocalStorage(key, defaults, options);
    }

    const {shallow} = options ?? {};
    return (shallow ? shallowRef : ref)(defaults) as RemovableRef<T>;
}
