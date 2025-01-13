import useVuelidate, {GlobalConfig, Validation, ValidationArgs} from "@vuelidate/core";
import {useResettableRef} from "~/functions/useResettableRef";
import {computed, ComputedRef, MaybeRef, unref} from "vue";
import {useEventBus} from "@vueuse/core";
import {cloneDeep, merge} from "lodash";
import {Ref} from "vue-demi";

type ValidationFunc = (options: GlobalConfig) => ValidationArgs
type BlankFormFunc = (options: GlobalConfig) => Record<string, any>

export type VuelidateValidations = ValidationArgs | ValidationFunc
export type VuelidateBlankForm = MaybeRef<Record<string, any> | BlankFormFunc>

export function useVuelidateOnForm(
    validations?: VuelidateValidations = {},
    blankForm?: VuelidateBlankForm = {},
    options?: GlobalConfig = {}
): {
    form: Ref<Record<string, any>>,
    resetForm(): void,
    v$: Ref<Validation>,
    isValid: ComputedRef<boolean>,
    validate(): Promise<boolean>,
    ifValid(cb: () => void): void
} {
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

    const ifValid = (cb: () => void) => {
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
