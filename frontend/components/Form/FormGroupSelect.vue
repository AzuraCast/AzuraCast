<template>
    <form-group
        v-bind="$attrs"
        :id="id"
    >
        <template
            v-if="label || slots.label"
            #label="slotProps"
        >
            <form-label
                :is-required="isRequired"
                :advanced="props.advanced"
                :high-cpu="props.highCpu"
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
                v-bind="{ id, field, model, class: fieldClass }"
            >
                <select
                    :id="id"
                    v-model="model"
                    class="form-select"
                    :class="fieldClass"
                    :multiple="multiple"
                >
                    <select-options :options="options" />
                </select>
            </slot>

            <vuelidate-error
                v-if="isVuelidateField"
                :field="field"
            />
        </template>

        <template
            v-if="description || slots.description"
            #description="slotProps"
        >
            <slot
                v-bind="slotProps"
                name="description"
            >
                {{ description }}
            </slot>
        </template>
    </form-group>
</template>

<script setup lang="ts" generic="T = ModelFormField">
import VuelidateError from "./VuelidateError.vue";
import FormLabel, {FormLabelParentProps} from "~/components/Form/FormLabel.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import {FormFieldEmits, FormFieldProps, ModelFormField, useFormField} from "~/components/Form/useFormField";
import SelectOptions from "~/components/Form/SelectOptions.vue";
import {useSlots} from "vue";
import {NestedFormOptionInput} from "~/functions/objectToFormOptions.ts";

interface FormGroupSelectProps extends FormFieldProps<T>, FormLabelParentProps {
    id: string,
    name?: string,
    label?: string,
    description?: string,
    options: NestedFormOptionInput,
    multiple?: boolean,
}

const props = withDefaults(
    defineProps<FormGroupSelectProps>(),
    {
        name: null,
        label: null,
        description: null,
        multiple: false
    }
);

const slots = useSlots();

const emit = defineEmits<FormFieldEmits<T>>();

const {model, isVuelidateField, fieldClass, isRequired} = useFormField<T>(props, emit);
</script>
