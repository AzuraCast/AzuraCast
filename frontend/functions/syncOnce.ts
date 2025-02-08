import {watchOnce} from "@vueuse/core";
import {MaybeRef, Ref, ref, toRef} from "vue";

/**
 * Creates a ref that syncs with its "source" value only once.
 * Useful for, for example, showing a loading value on initial load, but not on
 * subsequent refreshes.
 */
export default function syncOnce<T = unknown>(sourceMaybeRef: MaybeRef<T>): Ref<T> {
    const sourceRef = toRef(sourceMaybeRef);

    const newRef = ref(sourceRef.value);
    watchOnce(sourceRef, (newVal) => {
        newRef.value = newVal;
    });

    return newRef;
}
