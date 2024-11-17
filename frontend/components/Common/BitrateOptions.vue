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
            <form-multi-check
                :id="id"
                v-model="radioField"
                :name="name || id"
                :options="bitrateOptions"
                radio
                stacked
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

                <template #label(custom)>
                    {{ $gettext('Custom') }}

                    <input
                        :id="id+'_custom'"
                        v-model="customField"
                        class="form-control form-control-sm"
                        type="number"
                        min="1"
                        max="4096"
                        step="1"
                    >
                </template>
            </form-multi-check>
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

<script setup lang="ts">
import {formFieldProps, useFormField} from "~/components/Form/useFormField";
import {computed, ComputedRef, useSlots} from "vue";
import {includes, map} from "lodash";
import useSlotsExcept from "~/functions/useSlotsExcept.ts";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import FormLabel from "~/components/Form/FormLabel.vue";
import FormGroup from "~/components/Form/FormGroup.vue";

const props = defineProps({
    ...formFieldProps,
    id: {
        type: String,
        required: true
    },
    maxBitrate: {
        type: Number,
        required: true
    },
    name: {
        type: String,
        default: null,
    },
    label: {
        type: String,
        default: null
    },
    description: {
        type: String,
        default: null
    },
    advanced: {
        type: Boolean,
        default: false
    }
});

const slots = useSlots();

const emit = defineEmits(['update:modelValue']);

const {model} = useFormField(props, emit);

const radioBitrates = [
    32, 48, 64, 96, 128, 192, 256, 320
].filter((bitrate) => props.maxBitrate === 0 || bitrate <= props.maxBitrate);

const customField: ComputedRef<number | null> = computed({
    get() {
        return includes(radioBitrates, model.value)
            ? ''
            : model.value;
    },
    set(newValue) {
        model.value = newValue;
    }
});

const radioField: ComputedRef<number | string | null> = computed({
    get() {
        return includes(radioBitrates, model.value)
            ? model.value
            : 'custom';
    },
    set(newValue) {
        if (newValue !== 'custom') {
            model.value = newValue;
        }
    }
});

const bitrateOptions = map(
    radioBitrates,
    (val) => {
        return {
            value: val,
            text: val
        };
    }
);

bitrateOptions.push({
    value: 'custom',
    text: 'Custom'
});
</script>
