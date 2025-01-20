import {computed, ComputedRef, WritableComputedRef} from "vue";
import {has} from "lodash";
import {BaseValidation, ValidationRuleCollection} from "@vuelidate/core";
import {reactiveComputed} from "@vueuse/core";

export type ModelFormField = string | number | boolean | Array<any> | null

export type VuelidateField<T = ModelFormField> = BaseValidation<T, ValidationRuleCollection<T>>

export interface FormFieldProps<T = ModelFormField> {
    modelValue?: T
    field?: VuelidateField<T>
    required?: boolean,
}

export interface FormFieldEmits<T = ModelFormField> {
    (e: 'update:modelValue', value: T): void
}

export function useFormField<T = ModelFormField>(
    initialProps: FormFieldProps<T>,
    emit: FormFieldEmits<T>
): {
    isVuelidateField: ComputedRef<boolean>,
    model: WritableComputedRef<T>,
    fieldClass: ComputedRef<string | null>,
    isRequired: ComputedRef<boolean>
} {
    const props = reactiveComputed(() => ({
        required: false,
        ...initialProps
    })) as FormFieldProps<T>;

    const isVuelidateField = computed(
        () => props.field !== undefined
    );

    const model: WritableComputedRef<T, T> = computed({
        get() {
            return (props.field)
                ? props.field.$model
                : props.modelValue;
        },
        set(newValue) {
            if (props.field) {
                props.field.$model = newValue;
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
            ? has(props.field, 'required')
            : false;
    });

    return {
        isVuelidateField,
        model,
        fieldClass,
        isRequired
    }
}
