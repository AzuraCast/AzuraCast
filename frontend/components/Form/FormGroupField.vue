<template>
    <form-group
        v-bind="$attrs"
        :id="id"
    >
        <template
            v-if="label || slots.label"
            #label
        >
            <form-label
                :is-required="isRequired"
                :advanced="props.advanced"
                :high-cpu="props.highCpu"
            >
                <slot name="label">
                    {{ label }}
                </slot>
            </form-label>
        </template>

        <template #default>
            <slot
                name="default"
                v-bind="{ id, field, model: modelObject, fieldClass, inputAttrs }"
            >
                <textarea
                    v-if="inputType === 'textarea'"
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="model"
                    :name="name"
                    :required="isRequired"
                    class="form-control"
                    :class="fieldClass"
                />
                <input
                    v-else
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="model"
                    :type="inputType"
                    :name="name"
                    :required="isRequired"
                    class="form-control"
                    :class="fieldClass"
                >
            </slot>

            <validation-error
                v-if="field"
                :field="field"
            />
        </template>

        <template
            v-if="description || slots.description || clearable"
            #description
        >
            <div
                v-if="clearable"
                class="buttons"
            >
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click.prevent="clear"
                >
                    {{ $gettext('Clear Field') }}
                </button>
            </div>

            <slot name="description">
                {{ description }}
            </slot>
        </template>
    </form-group>
</template>

<script setup lang="ts">
import {computed, nextTick, onMounted, reactive, Reactive, useTemplateRef, WritableComputedRef} from "vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormLabel, {FormLabelParentProps} from "~/components/Form/FormLabel.vue";
import {FormFieldEmits, FormFieldProps, useFormField, ValidatedField} from "~/components/Form/useFormField";
import ValidationError from "~/components/Form/ValidationError.vue";

type T = string | number | null;

type FormGroupFieldProps = FormFieldProps<T> & FormLabelParentProps & {
    id: string,
    name?: string,
    label?: string,
    description?: string,
    inputType?: string,
    inputNumber?: boolean,
    inputTrim?: boolean,
    inputEmptyIsNull?: boolean,
    inputAttrs?: object,
    autofocus?: boolean,
    clearable?: boolean,
}

interface FilteredModelObject {
    $model: WritableComputedRef<T>
}

const props = withDefaults(
    defineProps<FormGroupFieldProps>(),
    {
        inputType: 'text',
        inputNumber: false,
        inputTrim: false,
        inputEmptyIsNull: false,
        inputAttrs: () => ({}),
        autofocus: false,
        clearable: false
    }
);

const slots = defineSlots<{
    label?: () => any,
    default?: (props: {
        id: string,
        field?: ValidatedField<T>,
        model: {
            $model: T
        },
        inputAttrs?: object,
        fieldClass: string | null
    }) => any,
    description?: () => any,
}>();

const emit = defineEmits<FormFieldEmits<T>>();

const {model: parentModel, fieldClass, isRequired} = useFormField<T>(props, emit);

const isNumeric = computed(() => {
    return props.inputNumber || props.inputType === "number" || props.inputType === "range";
});

const model = computed({
    get() {
        return parentModel.value;
    },
    set(newValue) {
        if ((isNumeric.value || props.inputEmptyIsNull) && '' === newValue) {
            parentModel.value = null;
        } else {
            if (props.inputTrim && null !== newValue) {
                newValue = String(newValue).replace(/^\s+|\s+$/gm, '');
            }

            if (isNumeric.value) {
                newValue = Number(newValue);
            }

            parentModel.value = newValue;
        }
    }
});

// Work around a Vue v-model limitation by passing model as an object to child slots.
const modelObject: Reactive<FilteredModelObject> = reactive({
    $model: model
});

const $input = useTemplateRef<HTMLInputElement | HTMLTextAreaElement>('$input');

const focus = () => {
    $input.value?.focus();
};

const clear = () => {
    model.value = '';
}

onMounted(() => {
    if (props.autofocus) {
        void nextTick(() => {
            focus();
        });
    }
})

defineExpose({
    focus
});
</script>
