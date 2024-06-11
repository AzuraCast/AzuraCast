import useVuelidate from "@vuelidate/core";
import {useResettableRef} from "~/functions/useResettableRef";
import {computed, unref} from "vue";
import {useEventBus} from "@vueuse/core";
import {cloneDeep, merge} from "lodash";

export function useVuelidateOnForm(validations = {}, blankForm = {}, options = {}) {
    const formEventBus = useEventBus('form_tabs');

    // Build the blank form from any children elements to the one using this function.
    const buildBlankForm = () => {
        let parsedBlankForm = unref(blankForm);
        if (typeof parsedBlankForm === 'function') {
            parsedBlankForm = parsedBlankForm(options);
        }

        parsedBlankForm = cloneDeep(parsedBlankForm);

        formEventBus.emit((originalNewForm) => {
            let newForm = unref(originalNewForm);
            if (typeof newForm === 'function') {
                newForm = newForm(options);
            }

            merge(parsedBlankForm, newForm);
        });

        return parsedBlankForm;
    }

    const {record: form, reset} = useResettableRef(buildBlankForm);

    const parsedValidations = (typeof validations === 'function')
        ? validations(options)
        : validations;

    const v$ = useVuelidate(parsedValidations, form, options);

    const resetForm = () => {
        reset();
        v$.value.$reset();
    }

    const isValid = computed(() => {
        return !v$.value.$invalid;
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
