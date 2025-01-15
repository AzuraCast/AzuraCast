import {computed, ComputedRef, WritableComputedRef} from "vue";
import {has} from "lodash";
import {VuelidateObject} from "~/functions/useVuelidateOnForm.ts";

export type ModelFormField = string | number | boolean | Array<any> | null

export interface FormFieldProps {
    field?: VuelidateObject,
    modelValue?: ModelFormField,
    required?: boolean
}

export interface FormFieldEmits {
    (e: 'update:modelValue', value: ModelFormField): void
}

export function useFormField(
    initialProps: FormFieldProps,
    emit: FormFieldEmits
): {
    isVuelidateField: ComputedRef<boolean>,
    model: WritableComputedRef<ModelFormField>,
    fieldClass: ComputedRef<string | null>,
    isRequired: ComputedRef<boolean>
} {
    const props: FormFieldProps = {
        required: false,
        ...initialProps
    };

    const isVuelidateField = computed(
        () => props.field !== undefined
    );

    const model: WritableComputedRef<ModelFormField> = computed({
        get() {
            return (isVuelidateField.value)
                ? props.field.$model as ModelFormField
                : props.modelValue;
        },
        set(newValue: ModelFormField) {
            if (isVuelidateField.value) {
                // @ts-expect-error Vuelidate mistypes this.
                props.field.$model = newValue;
            } else {
                emit('update:modelValue', newValue);
            }
        }
    });

    const fieldClass = computed(() => {
        if (!isVuelidateField.value) {
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

        return (isVuelidateField.value)
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
