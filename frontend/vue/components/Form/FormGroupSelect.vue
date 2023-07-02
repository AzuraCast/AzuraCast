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
                <select
                    :id="id"
                    v-model="field.$model"
                    class="form-control"
                    :class="fieldClass"
                    :multiple="multiple"
                >
                    <form-group-select-option :options="options" />
                </select>
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
import {has} from "lodash";
import VuelidateError from "./VuelidateError";
import {computed} from "vue";
import FormLabel from "~/components/Form/FormLabel.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import useFormFieldState from "~/functions/useFormFieldState";
import FormGroupSelectOption from "~/components/Form/FormGroupSelectOption.vue";

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
    options: {
        type: Array,
        required: true
    },
    multiple: {
        type: Boolean,
        default: false
    },
    advanced: {
        type: Boolean,
        default: false
    }
});

const fieldClass = useFormFieldState(props.field);

const isRequired = computed(() => {
    return has(props.field, 'required');
});
</script>
