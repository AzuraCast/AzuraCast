import useVuelidate, {GlobalConfig} from "@vuelidate/core";
import {computed, ComputedRef, WritableComputedRef} from "vue";
import {useEventBus, useVModel} from "@vueuse/core";
import {GenericForm} from "~/entities/Forms.ts";
import {VuelidateRef, VuelidateValidations} from "~/functions/useVuelidateOnForm.ts";

export interface FormTabProps<T extends GenericForm = GenericForm> {
    form: T,
}

export interface FormTabEmits<T extends GenericForm = GenericForm> {
    (e: 'update-form', form: T)
}

export function useVuelidateOnFormTab<T extends GenericForm = GenericForm, K extends Partial<T>>(
    props: FormTabProps<T>,
    emit: FormTabEmits<T>,
    validations: VuelidateValidations<K>,
    blankForm: K = {},
    vuelidateOptions: GlobalConfig = {}
): {
    form: WritableComputedRef<T>,
    v$: VuelidateRef<K>,
    isValid: ComputedRef<boolean>,
    tabClass: ComputedRef<string | null>
} {
    const form = useVModel(props, 'form', emit);

    const v$ = useVuelidate(validations, form, vuelidateOptions);

    const isValid = computed(() => {
        return !v$.value.$invalid;
    });

    const tabClass = computed(() => {
        if (v$.value.$anyDirty && v$.value.$invalid) {
            return 'text-danger';
        }
        return null;
    });

    // Register event listener for blankForm building.
    const formEventBus = useEventBus('form_tabs');

    formEventBus.on((addToForm) => {
        addToForm(blankForm);
    });

    return {
        form,
        v$,
        isValid,
        tabClass
    };
}
