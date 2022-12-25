<template>
    <b-form-group v-bind="$attrs" :label-for="id" :state="fieldState">
        <template #default>
            <slot name="default" v-bind="{ id, field, state: fieldState }">
                <b-form-textarea v-bind="inputAttrs" v-if="inputType === 'textarea'" ref="input" :id="id" :name="name"
                                 v-model="modelValue" :required="isRequired" :number="isNumeric" :trim="inputTrim"
                                 :autofocus="autofocus" :state="fieldState"></b-form-textarea>
                <b-form-input v-bind="inputAttrs" v-else ref="input" :type="inputType" :id="id" :name="name"
                              v-model="modelValue" :required="isRequired" :number="isNumeric" :trim="inputTrim"
                              :autofocus="autofocus" :state="fieldState"></b-form-input>
            </slot>

            <b-form-invalid-feedback :state="fieldState">
                <vuelidate-error :field="field"></vuelidate-error>
            </b-form-invalid-feedback>
        </template>

        <template #label="slotProps">
            <slot v-bind="slotProps" name="label"></slot>
            <span v-if="isRequired" class="text-danger">
                <span aria-hidden="true">*</span>
                <span class="sr-only">Required</span>
            </span>
            <span v-if="advanced" class="badge small badge-primary ml-2">
                {{ $gettext('Advanced') }}
            </span>
        </template>
        <template #description="slotProps">
            <slot v-bind="slotProps" name="description"></slot>
        </template>

        <template v-for="(_, slot) of filteredSlots" v-slot:[slot]="scope">
            <slot :name="slot" v-bind="scope"></slot>
        </template>
    </b-form-group>
</template>

<script setup>
import VuelidateError from "./VuelidateError";
import {computed, ref} from "vue";
import useSlotsExcept from "~/functions/useSlotsExcept";
import {has} from "lodash";

const props = defineProps({
    id: {
        type: String,
        required: true
    },
    name: {
        type: String,
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

const input = ref(); // Input

const focus = () => {
    input.value?.focus();
};

defineExpose({
    focus
});
</script>
