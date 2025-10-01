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
                v-bind="{ id, field, model }"
            >
                <form-multi-check
                    :id="id"
                    v-model="model"
                    :name="name || id"
                    :options="options"
                    :radio="radio"
                    :stacked="stacked"
                >
                    <template
                        v-for="(_, slot) of useSlotsExcept(['default', 'label', 'description'])"
                        #[slot]="scope"
                    >
                        <slot
                            :name="slot"
                            v-bind="scope"
                        />
                    </template>
                </form-multi-check>
            </slot>

            <validation-error
                v-if="field"
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
import FormLabel, {FormLabelParentProps} from "~/components/Form/FormLabel.vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import useSlotsExcept from "~/functions/useSlotsExcept";
import {FormFieldEmits, FormFieldProps, ModelFormField, useFormField} from "~/components/Form/useFormField";
import {useSlots} from "vue";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import ValidationError from "~/components/Form/ValidationError.vue";

type FormGroupMultiCheckProps = FormFieldProps<T> & FormLabelParentProps & {
    id: string,
    name?: string,
    label?: string,
    description?: string,
    options: SimpleFormOptionInput,
    radio?: boolean,
    stacked?: boolean
}

const props = withDefaults(
    defineProps<FormGroupMultiCheckProps>(),
    {
        radio: false,
        stacked: false
    }
);

const slots = useSlots();

const emit = defineEmits<FormFieldEmits<T>>();

const {model, isRequired} = useFormField<T>(props, emit);
</script>
