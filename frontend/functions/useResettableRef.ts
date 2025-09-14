import {MaybeRefOrGetter, Ref, ref, toValue} from "vue";
import {cloneDeep} from "es-toolkit";

export function useResettableRef<T = any>(
    original: MaybeRefOrGetter<T>
): { record: Ref<T>; reset: () => void } {
    const record = ref(cloneDeep(toValue(original))) as Ref<T>;

    const reset = () => {
        record.value = cloneDeep(toValue(original));
    }

    return {record, reset};
}
