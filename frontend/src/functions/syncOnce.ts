import {resolveRef, watchOnce} from "@vueuse/core";
import {ref} from "vue";

/**
 * Creates a ref that syncs with its "source" value only once.
 * Useful for, for example, showing a loading value on initial load, but not on
 * subsequent refreshes.
 */
export default function syncOnce(sourceMaybeRef) {
    const sourceRef = resolveRef(sourceMaybeRef);

    const newRef = ref(sourceRef.value);
    watchOnce(sourceRef, (newVal) => {
        newRef.value = newVal;
    });

    return newRef;
}
