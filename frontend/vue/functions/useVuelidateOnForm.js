import useVuelidate from "@vuelidate/core";
import {useResettableRef} from "~/functions/useResettableRef";
import {computed} from "vue";

export function useVuelidateOnForm(validations, blankForm, options = {}) {
    const {record: form, reset} = useResettableRef(blankForm);

    const v$ = useVuelidate(validations, form, options);

    const resetForm = () => {
        v$.value.$reset();
        reset();
    }

    const isValid = computed(() => {
        return !v$.value.$invalid ?? true;
    });

    const validate = () => {
        v$.value.$touch();

        return v$.value.$validate();
    }

    const ifValid = (cb) => {
        validate().then((isValid) => {
            if (!isValid) {
                return;
            }

            cb();
        });
    }

    return {
        form,
        resetForm,
        v$,
        isValid,
        validate,
        ifValid
    };
}
