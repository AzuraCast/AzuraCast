import {computed, UnwrapNestedRefs, WritableComputedRef} from "vue";
import {reactiveComputed} from "@vueuse/core";
import {InferRegleRules, InferRegleShortcuts, RegleFieldStatus} from "@regle/core";
import {useAppRegle} from "~/vendor/regle.ts";

export type ModelFormField = string | number | boolean | Array<any> | null | undefined

export type ValidatedField<T = ModelFormField> = RegleFieldStatus<
    T,
    InferRegleRules<typeof useAppRegle>,
    InferRegleShortcuts<typeof useAppRegle>
>

export type FormFieldProps<T = ModelFormField> = {
    required?: boolean
} & ({
    modelValue: T,
    field?: never
} | {
    field: ValidatedField<T>
    modelValue?: never
})

export interface FormFieldEmits<T = ModelFormField> {
    (e: 'update:modelValue', value: T): void
}

export function useFormField<T = ModelFormField>(
    initialProps: FormFieldProps<T>,
    emit: FormFieldEmits<T>
) {
    const props = reactiveComputed(() => ({
        required: false,
        ...initialProps
    })) as FormFieldProps<T>;

    const model: WritableComputedRef<T, T> = computed({
        get() {
            return (props.field !== undefined)
                ? props.field.$value as T
                : props.modelValue;
        },
        set(newValue) {
            if (props.field) {
                props.field.$value = newValue as UnwrapNestedRefs<T>;
                props.field.$touch();
            } else {
                emit('update:modelValue', newValue);
            }
        }
    });

    const fieldClass = computed(() => {
        if (!props.field) {
            return null;
        }

        if (!props.field.$dirty) {
            return null;
        }

        return props.field.$error
            ? 'is-invalid'
            : 'is-valid';
    });

    const isRequired = computed(() => {
        if (props.required) {
            return props.required;
        }

        return (props.field !== undefined)
            ? !!props.field.$rules?.required?.$active
            : false;
    });

    return {
        model,
        fieldClass,
        isRequired
    }
}
