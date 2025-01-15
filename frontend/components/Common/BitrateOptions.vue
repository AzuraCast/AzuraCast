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
import {FormFieldProps, useFormField} from "~/components/Form/useFormField";
import {computed, useSlots, WritableComputedRef} from "vue";
import {includes, map} from "lodash";
import useSlotsExcept from "~/functions/useSlotsExcept.ts";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import FormLabel, {FormLabelParentProps} from "~/components/Form/FormLabel.vue";
import FormGroup from "~/components/Form/FormGroup.vue";

interface BitrateOptionsProps extends FormFieldProps, FormLabelParentProps {
    id: string,
    maxBitrate: number,
    name?: string,
    label?: string,
    description?: string,
}

const props = withDefaults(
    defineProps<BitrateOptionsProps>(),
    {
        name: null,
        label: null,
        description: null,
    }
);

const slots = useSlots();

const emit = defineEmits(['update:modelValue']);

const {model, isRequired} = useFormField(props, emit);

const radioBitrates = [
    32, 48, 64, 96, 128, 192, 256, 320
].filter((bitrate) => props.maxBitrate === 0 || bitrate <= props.maxBitrate);

const customField: WritableComputedRef<number | null> = computed({
    get() {
        return includes(radioBitrates, model.value)
            ? ''
            : model.value;
    },
    set(newValue) {
        model.value = newValue;
    }
});

const radioField: WritableComputedRef<number | string | null> = computed({
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
    (val: number) => {
        return {
            value: val,
            text: String(val)
        };
    }
);

bitrateOptions.push({
    value: 'custom',
    text: 'Custom'
});
</script>
