<template>
    <select
        v-model="model"
        class="form-select"
        multiple
        :disabled="disabled"
    >
        <select-options :options="options"/>
    </select>
</template>

<script setup lang="ts" generic="T = ModelFormField">
import SelectOptions from "~/components/Form/SelectOptions.vue";
import { ModelFormField } from "~/components/Form/useFormField.ts";
import { NestedFormOptionInput } from "~/functions/objectToFormOptions.ts";

withDefaults(
    defineProps<{
        options: NestedFormOptionInput,
        disabled?: boolean
    }>(),
    {
        disabled: false
    }
);

const model = defineModel<T>();
</script>
