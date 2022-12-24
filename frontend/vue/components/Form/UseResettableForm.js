import {ref} from "vue";
import {cloneDeep} from "lodash";

export function useResettableForm(blankForm) {
    const form = ref(cloneDeep(blankForm));

    const resetForm = () => {
        form.value = cloneDeep(blankForm);
    }

    return {form, resetForm};
}
