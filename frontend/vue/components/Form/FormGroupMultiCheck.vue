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
                <form-multi-check
                    :id="id"
                    v-model="field.$model"
                    :name="name"
                    :options="options"
                    :radio="radio"
                    :stacked="stacked"
                >
                    <template
                        v-for="(_, slot) of useSlotsExcept($slots, ['default', 'label', 'description'])"
                        #[slot]="scope"
                    >
                        <slot
                            :name="slot"
                            v-bind="scope"
                        />
                    </template>
                </form-multi-check>
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
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import useSlotsExcept from "~/functions/useSlotsExcept";

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
    radio: {
        type: Boolean,
        default: false
    },
    stacked: {
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
