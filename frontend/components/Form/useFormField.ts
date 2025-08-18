import {computed, ComputedRef, UnwrapNestedRefs, WritableComputedRef} from "vue";
import {has} from "lodash";
import {reactiveComputed} from "@vueuse/core";
import {RegleFieldStatus} from "@regle/core";

export type ModelFormField = string | number | boolean | Array<any> | null | undefined

export type ValidatedField<T = ModelFormField> = RegleFieldStatus<T>

export interface FormFieldProps<T = ModelFormField> {
    modelValue?: T
    field?: ValidatedField<T>
    required?: boolean,
}

export interface FormFieldEmits<T = ModelFormField> {
    (e: 'update:modelValue', value: T): void
}

export function useFormField<T = ModelFormField>(
    initialProps: FormFieldProps<T>,
    emit: FormFieldEmits<T>
): {
    isValidatedField: ComputedRef<boolean>,
    model: WritableComputedRef<T>,
    fieldClass: ComputedRef<string | null>,
    isRequired: ComputedRef<boolean>
} {
    const props = reactiveComputed(() => ({
        required: false,
        ...initialProps
    })) as FormFieldProps<T>;

    const isValidatedField = computed(
        () => props.field !== undefined
    );

    const model: WritableComputedRef<T, T> = computed({
        get() {
            return (props.field)
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
            ? has(props.field, '$rules.required')
            : false;
    });

    return {
        isValidatedField,
        model,
        fieldClass,
        isRequired
    }
}
