import {computed} from "vue";
import {has} from "lodash";

export const formFieldProps = {
    field: {
        type: Object,
        required: false,
        default: () => {
            return undefined;
        }
    },
    modelValue: {
        type: [String, Number, Boolean, Array],
        required: false,
        default: () => {
            return undefined;
        }
    },
    required: {
        type: Boolean,
        default: false
    }
}

export function useFormField(props, emit) {
    const isVuelidateField = computed(() => {
        return props.field !== undefined;
    });

    const model = computed({
        get() {
            return (isVuelidateField.value)
                ? props.field.$model
                : props.modelValue;
        },
        set(newValue) {
            if (isVuelidateField.value) {
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
