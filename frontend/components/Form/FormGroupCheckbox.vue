<template>
    <form-group
        v-bind="$attrs"
        :id="id"
    >
        <template #default>
            <slot
                name="default"
                v-bind="{ id, field, model }"
            >
                <div class="form-check form-switch">
                    <form-checkbox
                        v-bind="inputAttrs"
                        :id="id"
                        v-model="model"
                        role="switch"
                        :name="name"
                    />
                    <label
                        class="form-check-label"
                        :for="id"
                    >
                        <form-label
                            :is-required="isRequired"
                            :advanced="props.advanced"
                            :high-cpu="props.highCpu"
                        >
                            <slot name="label">{{ label }}</slot>
                        </form-label>
                    </label>
                </div>
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
                name="description"
                v-bind="slotProps"
            >
                {{ description }}
            </slot>
        </template>
    </form-group>
</template>

<script setup lang="ts">
import VuelidateError from "./VuelidateError.vue";
import FormLabel, {FormLabelParentProps} from "~/components/Form/FormLabel.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import {FormFieldEmits, FormFieldProps, useFormField} from "~/components/Form/useFormField";
import {useSlots} from "vue";
import FormCheckbox from "~/components/Form/FormCheckbox.vue";

type T = boolean | null

interface FormGroupCheckboxProps extends FormFieldProps<T>, FormLabelParentProps {
    id: string,
    name?: string,
    label?: string,
    description?: string,
    inputAttrs?: object
}

const props = withDefaults(
    defineProps<FormGroupCheckboxProps>(),
    {
        name: null,
        label: null,
        description: null,
        inputAttrs: () => ({})
    }
);

const slots = useSlots();

const emit = defineEmits<FormFieldEmits<T>>();

const {model, isVuelidateField, isRequired} = useFormField<T>(props, emit);
</script>
