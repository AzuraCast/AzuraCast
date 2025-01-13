import {MaybeRefOrGetter, Ref, ref, toValue} from "vue";
import {cloneDeep} from "lodash";

export function useResettableRef<T>(original: MaybeRefOrGetter<T>): {
    record: Ref<T>,
    reset(): void
} {
    const record = ref(cloneDeep(toValue(original)));

    const reset = () => {
        record.value = cloneDeep(toValue(original));
    }

    return {record, reset};
}
