import useVuelidate from "@vuelidate/core";
import {useResettableForm} from "~/components/Form/UseResettableForm";

export function useVuelidateOnForm(validations, blankForm, options = {}) {
    const {form, resetForm: parentResetForm} = useResettableForm(blankForm);

    const v$ = useVuelidate(validations, form, options);

    const resetForm = () => {
        v$.value.$reset();
        parentResetForm();
    }

    return {
        form,
        resetForm,
        v$
    };
}
