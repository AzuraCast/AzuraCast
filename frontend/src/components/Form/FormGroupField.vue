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
                    v-model="filteredModel"
                    :name="name"
                    :required="isRequired"
                    class="form-control"
                    :class="fieldClass"
                />
                <input
                    v-else
                    v-bind="inputAttrs"
                    :id="id"
                    ref="$input"
                    v-model="filteredModel"
                    :type="inputType"
                    :name="name"
                    :required="isRequired"
                    class="form-control"
                    :class="fieldClass"
                >
            </slot>

            <vuelidate-error
                v-if="isVuelidateField"
                :field="field"
            />
        </template>

        <template
            v-if="description || slots.description || clearable"
            #description="slotProps"
        >
            <div
                v-if="clearable"
                class="buttons"
            >
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click.prevent="clear"
                >
                    {{ $gettext('Clear Field') }}
                </button>
            </div>

            <slot
                v-bind="slotProps"
                name="description"
            >
                {{ description }}
            </slot>
        </template>
    </form-group>
</template>

<script setup lang="ts">
import VuelidateError from "./VuelidateError.vue";
import {computed, nextTick, onMounted, ref, useSlots} from "vue";
import FormGroup from "~/components/Form/FormGroup.vue";
import FormLabel from "~/components/Form/FormLabel.vue";
import {formFieldProps, useFormField} from "~/components/Form/useFormField";

const props = defineProps({
    ...formFieldProps,
    id: {
        type: String,
        required: true
    },
    name: {
        type: String,
        default: null
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
    clearable: {
        type: Boolean,
        default: false
    },
    advanced: {
        type: Boolean,
        default: false
    }
});

const slots = useSlots();

const emit = defineEmits(['update:modelValue']);

const {model, isVuelidateField, fieldClass, isRequired} = useFormField(props, emit);

const isNumeric = computed(() => {
    return props.inputNumber || props.inputType === "number" || props.inputType === "range";
});

const filteredModel = computed({
    get() {
        return model.value;
    },
    set(newValue) {
        if ((isNumeric.value || props.inputEmptyIsNull) && '' === newValue) {
            model.value = null;
        } else {
            if (props.inputTrim && null !== newValue) {
                newValue = newValue.replace(/^\s+|\s+$/gm, '');
            }

            if (isNumeric.value) {
                newValue = Number(newValue);
            }

            model.value = newValue;
        }
    }
});

const $input = ref<HTMLInputElement | HTMLTextAreaElement | null>(null);

const focus = () => {
    $input.value?.focus();
};

const clear = () => {
    filteredModel.value = '';
}

onMounted(() => {
    if (props.autofocus) {
        nextTick(() => {
            focus();
        });
    }
})

defineExpose({
    focus
});
</script>
