import useVuelidate from "@vuelidate/core";
import {useResettableRef} from "~/functions/useResettableRef";

export function useVuelidateOnForm(validations, blankForm, options = {}) {
    const {record: form, reset} = useResettableRef(blankForm);

    const v$ = useVuelidate(validations, form, options);

    const resetForm = () => {
        v$.value.$reset();
        reset();
    }

    return {
        form,
        resetForm,
        v$
    };
}
