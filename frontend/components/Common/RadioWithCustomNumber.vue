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
                v-model.number="customField"
                v-bind="inputAttrs"
                @focus="onCustomFieldFocus"
                class="form-control form-control-sm"
                type="number"
            >
        </template>
    </form-multi-check>
</template>

<script setup lang="ts" generic="T = string | number | null">
import {FormFieldProps} from "~/components/Form/useFormField";
import {computed, nextTick, ref, toRef, useSlots} from "vue";
import {find} from "es-toolkit/compat";
import FormMultiCheck from "~/components/Form/FormMultiCheck.vue";
import {objectToSimpleFormOptions, SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {pausableWatch, WatchPausableReturn} from "@vueuse/core";

type RadioCustomNumberProps = FormFieldProps<T> & {
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

const toNumberOrNull = (value: any): number | null => {
    if (value === null) {
        return value;
    }

    value = Number(value);
    return Number.isNaN(value) ? null : value;
};

const model = defineModel<T, string, number | null, string | number | null>({
    default: null,
    get(value) {
        return toNumberOrNull(value);
    },
    set(value) {
        return toNumberOrNull(value);
    }
});

const originalOptions = objectToSimpleFormOptions(toRef(props, 'options'));

const optionsWithCustom = computed(() => {
    const parsedOptions = [
        ...originalOptions.value
    ];
    
    parsedOptions.push({
        value: 'custom',
        text: 'Custom'
    });

    return parsedOptions;
});

const radioField = ref<"custom" | T | null>(null);
const customField = ref<T | null>(null);

const watchers: WatchPausableReturn[] = [];

// Sync from models to others.
watchers.push(pausableWatch(
    model,
    async (newValue) => {
        watchers.forEach(w => w.pause());

        if (find(originalOptions.value, {
            value: newValue
        })) {
            radioField.value = newValue;
            customField.value = null;
        } else {
            radioField.value = "custom";
            customField.value = newValue;
        }

        await nextTick();

        watchers.forEach(w => w.resume());
    },
    {
        flush: 'sync',
        immediate: true
    }
));

// Sync radio to model and others
watchers.push(pausableWatch(
    radioField,
    async (newValue) => {
        watchers.forEach(w => w.pause());

        if (newValue === "custom") {
            customField.value = model.value;
        } else {
            customField.value = null;
            model.value = newValue;
        }
        await nextTick();

        watchers.forEach(w => w.resume());
    }, {
        flush: 'sync'
    }
));

// Sync custom field to model and others
watchers.push(pausableWatch(
    customField,
    async (newValue) => {
        watchers.forEach(w => w.pause());

        model.value = newValue;
        await nextTick();

        watchers.forEach(w => w.resume());
    }, {
        flush: 'sync'
    }
));

const onCustomFieldFocus = () => {
    radioField.value = 'custom';
    customField.value = model.value;
}
</script>
