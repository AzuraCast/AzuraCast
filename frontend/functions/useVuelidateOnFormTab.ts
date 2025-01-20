import useVuelidate, {GlobalConfig, ValidationArgs} from "@vuelidate/core";
import {computed, ComputedRef, Ref, WritableComputedRef} from "vue";
import {useEventBus, useVModel} from "@vueuse/core";
import {GenericForm} from "~/entities/Forms.ts";
import {VuelidateRef} from "~/functions/useVuelidateOnForm.ts";

export interface FormTabProps<T extends GenericForm = GenericForm> {
    form: T,
}

export interface FormTabEmits<T extends GenericForm = GenericForm> {
    (e: 'update:form', form: T)
}

export function useVuelidateOnFormTab<
    ParentForm extends GenericForm = GenericForm,
    TabForm extends GenericForm = GenericForm
>(
    props: FormTabProps<ParentForm>,
    emit: FormTabEmits<ParentForm>,
    validations: Ref<ValidationArgs<TabForm>> | ValidationArgs<TabForm>,
    blankForm: TabForm,
    vuelidateOptions: GlobalConfig = {}
): {
    form: WritableComputedRef<ParentForm>,
    v$: VuelidateRef<TabForm>,
    isValid: ComputedRef<boolean>,
    tabClass: ComputedRef<string | null>
} {
    const form = useVModel(props, 'form', emit);

    const v$ = useVuelidate(validations, form as unknown as WritableComputedRef<TabForm>, vuelidateOptions);

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

    formEventBus.on((addToForm: (blankForm: TabForm) => void) => {
        addToForm(blankForm);
    });

    return {
        form,
        v$,
        isValid,
        tabClass
    };
}
