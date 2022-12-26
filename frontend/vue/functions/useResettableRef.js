import {ref} from "vue";
import {cloneDeep} from "lodash";

export function useResettableRef(original) {
    const record = ref(cloneDeep(original));

    const reset = () => {
        record.value = cloneDeep(original);
    }

    return {record, reset};
}
