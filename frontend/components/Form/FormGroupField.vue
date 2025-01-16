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
                v-bind="{ id, field, model: filteredModelObject, class: fieldClass }"
            >
                <textarea
                    v-if="inputType === 'textarea'"
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="filteredModel"
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
                    v-model="filteredModel"
                    :type="inputType"
                    :name="name"
                    :required="isRequired"
                    class="form-control"
                    :class="fieldClass"
                >
            </slot>

            <vuelidate-error
                v-if="isVuelidateField"
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

<script setup lang="ts" generic="T extends any = ModelFormField">
import VuelidateError from "./VuelidateError.vue";
import {computed, ComputedRef, nextTick, onMounted, reactive, Reactive, ref} from "vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormLabel, {FormLabelParentProps} from "~/components/Form/FormLabel.vue";
import {
    FormFieldEmits,
    FormFieldProps,
    ModelFormField,
    useFormField,
    VuelidateField
} from "~/components/Form/useFormField";

interface FormGroupFieldProps extends FormFieldProps<T>, FormLabelParentProps {
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
    $model: T
}

const props = withDefaults(
    defineProps<FormGroupFieldProps>(),
    {
        name: null,
        label: null,
        description: null,
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
        field?: VuelidateField<T>,
        model: FilteredModelObject,
        class: ComputedRef<string | null>
    }) => any,
    description?: () => any,
}>();

const emit = defineEmits<FormFieldEmits<T>>();

const {model, isVuelidateField, fieldClass, isRequired} = useFormField<T>(props, emit);

const isNumeric = computed(() => {
    return props.inputNumber || props.inputType === "number" || props.inputType === "range";
});

const filteredModel = computed({
    get() {
        return model.value;
    },
    set(newValue) {
        if ((isNumeric.value || props.inputEmptyIsNull) && '' === newValue) {
            model.value = null;
        } else {
            if (props.inputTrim && null !== newValue) {
                newValue = newValue.replace(/^\s+|\s+$/gm, '');
            }

            if (isNumeric.value) {
                newValue = Number(newValue);
            }

            model.value = newValue;
        }
    }
});

// Work around a Vue v-model limitation by passing model as an object to child slots.
const filteredModelObject: Reactive<FilteredModelObject> = reactive({
    $model: filteredModel
});

const $input = ref<HTMLInputElement | HTMLTextAreaElement | null>(null);

const focus = () => {
    $input.value?.focus();
};

const clear = () => {
    filteredModel.value = '';
}

onMounted(() => {
    if (props.autofocus) {
        nextTick(() => {
            focus();
        });
    }
})

defineExpose({
    focus
});
</script>
