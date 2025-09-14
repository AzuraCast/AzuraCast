<template>
    <select
        v-model="model"
        class="form-select"
        multiple
    >
        <select-options :options="options"/>
    </select>
</template>

<script setup lang="ts" generic="T = ModelFormField">
import {NestedFormOptionInput} from "~/functions/objectToFormOptions.ts";
import SelectOptions from "~/components/Form/SelectOptions.vue";
import {ModelFormField} from "~/components/Form/useFormField.ts";

defineProps<{
    options: NestedFormOptionInput
}>();

const model = defineModel<T>({
    default: () => [] as any[],
});
</script>
