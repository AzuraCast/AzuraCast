<template>
    <b-form-group
        v-bind="$attrs"
        :label-for="id"
        :state="fieldState"
    >
        <template #default>
            <slot
                name="default"
                v-bind="{ id, field, state: fieldState }"
            >
                <b-form-textarea
                    v-if="inputType === 'textarea'"
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="modelValue"
                    :name="name"
                    :required="isRequired"
                    :number="isNumeric"
                    :trim="inputTrim"
                    :autofocus="autofocus"
                    :state="fieldState"
                />
                <b-form-input
                    v-else
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="modelValue"
                    :type="inputType"
                    :name="name"
                    :required="isRequired"
                    :number="isNumeric"
                    :trim="inputTrim"
                    :autofocus="autofocus"
                    :state="fieldState"
                />
            </slot>

            <b-form-invalid-feedback :state="fieldState">
                <vuelidate-error :field="field" />
            </b-form-invalid-feedback>
        </template>

        <template #label="slotProps">
            <slot
                v-bind="slotProps"
                name="label"
            />
            <span
                v-if="isRequired"
                class="text-danger"
            >
                <span aria-hidden="true">*</span>
                <span class="sr-only">Required</span>
            </span>
            <advanced-tag v-if="advanced" />
        </template>
        <template #description="slotProps">
            <slot
                v-bind="slotProps"
                name="description"
            />
        </template>

        <template
            v-for="(_, slot) of filteredSlots"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope"
            />
        </template>
    </b-form-group>
</template>

<script setup>
import VuelidateError from "./VuelidateError";
import {computed, ref} from "vue";
import useSlotsExcept from "~/functions/useSlotsExcept";
import {has} from "lodash";
import AdvancedTag from "./AdvancedTag";

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

const modelValue = computed({
    get() {
        return props.field.$model;
    },
    set(newValue) {
        if ((props.isNumeric || props.inputEmptyIsNull) && '' === newValue) {
            newValue = null;
        }

        props.field.$model = newValue;
    }
});

const filteredSlots = useSlotsExcept(['default', 'label', 'description']);

const fieldState = computed(() => {
    return props.field.$dirty ? !props.field.$error : null;
});

const isRequired = computed(() => {
    return has(props.field, 'required');
});

const isNumeric = computed(() => {
    return props.inputNumber || props.inputType === "number";
});

const $input = ref(); // Input

const focus = () => {
    $input.value?.focus();
};

defineExpose({
    focus
});
</script>
