import {ref} from "vue";
import {cloneDeep} from "lodash";
import {resolveUnref} from "@vueuse/core";

export function useResettableRef(original) {
    const record = ref(cloneDeep(resolveUnref(original)));

    const reset = () => {
        record.value = cloneDeep(resolveUnref(original));
    }

    return {record, reset};
}
