import {Ref, ref, toValue} from "vue";
import {cloneDeep} from "lodash";

export function useResettableRef<T = any>(
    original: { name: string; short_name: string; auto_assign: string }
): { record: Ref<T>; reset: () => void } {
    const record = ref(cloneDeep(toValue(original))) as Ref<T>;

    const reset = () => {
        record.value = cloneDeep(toValue(original));
    }

    return {record, reset};
}
