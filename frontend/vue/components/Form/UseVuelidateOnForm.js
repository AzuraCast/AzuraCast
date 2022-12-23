import useVuelidate from "@vuelidate/core";
import {useResettableForm} from "~/components/Form/UseResettableForm";

export function useVuelidateOnForm(validations, blankForm) {
    const {form, resetForm: parentResetForm} = useResettableForm(blankForm);

    const v$ = useVuelidate(validations, form);

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
