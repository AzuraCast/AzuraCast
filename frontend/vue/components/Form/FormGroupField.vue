<template>
    <form-group
        v-bind="$attrs"
        :id="id"
    >
        <template #label="slotProps">
            <form-label
                :is-required="isRequired"
                :advanced="advanced"
            >
                <slot
                    name="label"
                    v-bind="slotProps"
                >
                    {{ label }}
                </slot>
            </form-label>
        </template>

        <template #default>
            <slot
                name="default"
                v-bind="{ id, field, class: fieldClass }"
            >
                <textarea
                    v-if="inputType === 'textarea'"
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="modelValue"
                    :name="name"
                    :required="isRequired"
                    :autofocus="autofocus"
                    class="form-control"
                    :class="fieldClass"
                />
                <input
                    v-else
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="modelValue"
                    :type="inputType"
                    :name="name"
                    :required="isRequired"
                    :autofocus="autofocus"
                    class="form-control"
                    :class="fieldClass"
                >
            </slot>

            <vuelidate-error :field="field" />
        </template>

        <template #description="slotProps">
            <slot
                v-bind="slotProps"
                name="description"
            >
                {{ description }}
            </slot>
        </template>
    </form-group>
</template>

<script setup>
import VuelidateError from "./VuelidateError";
import {computed, ref} from "vue";
import {has} from "lodash";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormLabel from "~/components/Form/FormLabel.vue";
import useFormFieldState from "~/functions/useFormFieldState";

const props = defineProps({
    id: {
        type: String,
        required: true
    },
    name: {
        type: String,
        default: null
    },
    field: {
        type: Object,
        required: true
    },
    label: {
        type: String,
        default: null
    },
    description: {
        type: String,
        default: null
    },
    inputType: {
        type: String,
        default: 'text'
    },
    inputNumber: {
        type: Boolean,
        default: false
    },
    inputTrim: {
        type: Boolean,
        default: false
    },
    inputEmptyIsNull: {
        type: Boolean,
        default: false
    },
    inputAttrs: {
        type: Object,
        default() {
            return {};
        }
    },
    autofocus: {
        type: Boolean,
        default: false
    },
    advanced: {
        type: Boolean,
        default: false
    }
});

const isNumeric = computed(() => {
    return props.inputNumber || props.inputType === "number" || props.inputType === "range";
});

const modelValue = computed({
    get() {
        return props.field.$model;
    },
    set(newValue) {
        if ((isNumeric.value || props.inputEmptyIsNull) && '' === newValue) {
            newValue = null;
        }

        if (props.inputTrim && null !== newValue) {
            newValue = newValue.replace(/^\s+|\s+$/gm, '');
        }

        if (isNumeric.value) {
            newValue = Number(newValue);
        }

        props.field.$model = newValue;
    }
});

const fieldClass = useFormFieldState(props.field);

const isRequired = computed(() => {
    return has(props.field, 'required');
});

const $input = ref(); // Input

const focus = () => {
    $input.value?.focus();
};

defineExpose({
    focus
});
</script>
