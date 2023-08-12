<template>
    <form-group
        v-bind="$attrs"
        :id="id"
    >
        <template #default>
            <slot
                name="default"
                v-bind="{ id, field, class: fieldClass }"
            >
                <div class="form-check form-switch">
                    <input
                        v-bind="inputAttrs"
                        :id="id"
                        v-model="field.$model"
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        :name="name"
                    >
                    <label
                        class="form-check-label"
                        :for="id"
                    >
                        <form-label
                            :is-required="isRequired"
                            :advanced="advanced"
                        >
                            <slot name="label">{{ label }}</slot>
                        </form-label>
                    </label>
                </div>
            </slot>

            <vuelidate-error :field="field" />
        </template>

        <template #description="slotProps">
            <slot
                name="description"
                v-bind="slotProps"
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

const fieldClass = useFormFieldState(props.field);

const isRequired = computed(() => {
    return has(props.field, 'required');
});
</script>
