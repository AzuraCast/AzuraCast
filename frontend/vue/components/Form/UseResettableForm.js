import {ref} from "vue";

export function useResettableForm(blankForm) {
    const form = ref({...blankForm});

    const resetForm = () => {
        form.value = {...blankForm};
    }

    return {form, resetForm};
}
