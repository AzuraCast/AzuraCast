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
                <b-form-checkbox
                    v-bind="inputAttrs"
                    :id="id"
                    v-model="field.$model"
                    :name="name"
                >
                    <slot name="label" />
                    <span
                        v-if="isRequired"
                        class="text-danger"
                    >
                        <span aria-hidden="true">*</span>
                        <span class="sr-only">Required</span>
                    </span>
                    <advanced-tag v-if="advanced" />
                </b-form-checkbox>

                <b-form-invalid-feedback :state="fieldState">
                    <vuelidate-error :field="field" />
                </b-form-invalid-feedback>
            </slot>
        </template>

        <template #description="slotProps">
            <slot
                name="description"
                v-bind="slotProps"
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
import {has} from "lodash";
import VuelidateError from "./VuelidateError";
import useSlotsExcept from "~/functions/useSlotsExcept";
import {computed} from "vue";
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
    inputAttrs: {
        type: Object,
        default() {
            return {};
        }
    },
    advanced: {
        type: Boolean,
        default: false
    }
});

const filteredSlots = useSlotsExcept(['default', 'description']);

const fieldState = computed(() => {
    return props.field.$dirty ? !props.field.$error : null;
});

const isRequired = computed(() => {
    return has(props.field, 'required');
});
</script>
