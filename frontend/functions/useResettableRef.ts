import {ref, toValue} from "vue";
import {cloneDeep} from "lodash";

export function useResettableRef(original) {
    const record = ref(cloneDeep(toValue(original)));

    const reset = () => {
        record.value = cloneDeep(toValue(original));
    }

    return {record, reset};
}
