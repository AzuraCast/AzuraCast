import useVuelidate, {GlobalConfig, Validation, ValidationArgs} from "@vuelidate/core";
import {useResettableRef} from "~/functions/useResettableRef";
import {computed, ComputedRef, MaybeRef, unref} from "vue";
import {useEventBus} from "@vueuse/core";
import {cloneDeep, merge} from "lodash";
import {Ref} from "vue-demi";
import {GenericForm} from "~/entities/Forms.ts";

type ValidationFunc<T extends GenericForm = GenericForm> = (options: GlobalConfig) => ValidationArgs<T>
type BlankFormFunc<T extends GenericForm = GenericForm> = (options: GlobalConfig) => T

export type VuelidateValidations<T extends GenericForm = GenericForm> = ValidationArgs<T> | ValidationFunc<T>
export type VuelidateBlankForm<T extends GenericForm = GenericForm> = MaybeRef<T> | BlankFormFunc<T>

export type VuelidateObject<T extends GenericForm = GenericForm> = Validation<ValidationArgs<T>, T>
export type VuelidateRef<T extends GenericForm = GenericForm> = MaybeRef<VuelidateObject<T>>

export function useVuelidateOnForm<T extends GenericForm = GenericForm>(
    validations: VuelidateValidations<T> = {} as VuelidateValidations<T>,
    blankForm: VuelidateBlankForm<T> = {} as VuelidateBlankForm<T>,
    options: GlobalConfig = {}
): {
    form: Ref<T>,
    resetForm(): void,
    v$: VuelidateRef<T>,
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

        return parsedBlankForm as T;
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
