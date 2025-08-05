<template>
    <form-multi-check
        :id="id"
        :name="name || id"
        v-model="radioField"
        :options="optionsWithCustom"
        radio
        stacked
    >
        <template
            v-for="(_, slot) of slots"
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
                v-bind="inputAttrs"
                class="form-control form-control-sm"
                type="number"
            >
        </template>
    </form-multi-check>
</template>

<script setup lang="ts" generic="T = number | null">
import {FormFieldProps} from "~/components/Form/useFormField";
import {computed, toRef, useSlots, WritableComputedRef} from "vue";
import {find} from "lodash";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import {objectToSimpleFormOptions, SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";

interface RadioCustomNumberProps extends FormFieldProps<T> {
    id: string,
    name?: string,
    inputAttrs?: object,
    options: SimpleFormOptionInput,
}

const props = withDefaults(
    defineProps<RadioCustomNumberProps>(),
    {
        inputAttrs: () => ({
            min: 1,
            max: 4096,
            step: 1
        }),
    }
);

const slots = useSlots();

const model = defineModel<T>({
    default: null
});

const originalOptions = objectToSimpleFormOptions(toRef(props, 'options'));

const optionsWithCustom = computed(() => {
    const parsedOptions = originalOptions.value;
    parsedOptions.push({
        value: 'custom',
        text: 'Custom'
    });

    return parsedOptions;
});

const customField: WritableComputedRef<T> = computed({
    get() {
        return find(originalOptions.value, {
            value: Number(model.value)
        }) ? null : model.value;
    },
    set(newValue) {
        model.value = newValue;
    }
});

const radioField: WritableComputedRef<"custom" | T> = computed({
    get() {
        return find(originalOptions.value, {
            value: Number(model.value)
        }) ? model.value : 'custom';
    },
    set(newValue) {
        if (newValue !== 'custom') {
            model.value = newValue;
        }
    }
});
</script>
