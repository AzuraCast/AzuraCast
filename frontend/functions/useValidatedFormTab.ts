import useVuelidate, {GlobalConfig, ValidationArgs} from "@vuelidate/core";
import {computed, ComputedRef, ModelRef, Ref} from "vue";
import {useFormTabEventBus, VuelidateRef} from "~/functions/useValidatedParentForm.ts";
import {ApiGenericForm} from "~/entities/ApiInterfaces.ts";

export function useValidatedFormTab<
    ParentForm extends ApiGenericForm = ApiGenericForm,
    TabForm extends ApiGenericForm = Partial<ParentForm>
>(
    form: ModelRef<ParentForm>,
    validations: Ref<ValidationArgs<TabForm>> | ValidationArgs<TabForm>,
    blankForm: TabForm,
    vuelidateOptions: GlobalConfig = {}
): {
    v$: VuelidateRef<TabForm>,
    isValid: ComputedRef<boolean>,
    tabClass: ComputedRef<string>
} {
    const v$ = useVuelidate(validations, form as unknown as Ref<TabForm>, vuelidateOptions);

    const isValid = computed(() => {
        return !v$.value.$invalid;
    });

    const tabClass = computed(() => {
        if (v$.value.$anyDirty && v$.value.$invalid) {
            return 'text-danger';
        }
        return '';
    });

    // Register event listener for blankForm building.
    const formEventBus = useFormTabEventBus<TabForm>();

    formEventBus.on((_event, addToForm) => {
        addToForm(blankForm);
    });

    return {
        v$,
        isValid,
        tabClass
    };
}
