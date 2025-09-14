<template>
    <form-group-multi-check
        :id="id"
        v-bind="$attrs"
        v-model="model"
        :options="show24hourOptions"
        stacked
        radio
        :label="$gettext('Time Display')"
    />
</template>

<script setup lang="ts">

import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {computed} from "vue";
import {SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {FormFieldEmits, FormFieldProps, useFormField} from "~/components/Form/useFormField.ts";

type T = boolean | null;

const props = defineProps<FormFieldProps<T> & {
    id: string
}>();

const emit = defineEmits<FormFieldEmits<T>>();

const {model: parentModel} = useFormField<T>(props, emit);

const model = computed<string, string | boolean | null>({
    get() {
        const originalValue = parentModel.value;

        if (originalValue === true) {
            return "true";
        } else if (originalValue === false) {
            return "false";
        } else {
            return "";
        }
    },
    set(newValue: string | boolean | null) {
        if (newValue === true || newValue === "true") {
            parentModel.value = true;
        } else if (newValue === false || newValue === "false") {
            parentModel.value = false;
        } else {
            parentModel.value = null;
        }
    }
});

const {$gettext} = useTranslate();

const show24hourOptions = computed<SimpleFormOptionInput>(() => {
    return [
        {
            text: $gettext('Prefer System Default'),
            value: ""
        },
        {
            text: $gettext('12 Hour'),
            value: "false"
        },
        {
            text: $gettext('24 Hour'),
            value: "true"
        }
    ];
});
</script>
