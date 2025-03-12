<template>
    <template
        v-for="(option, index) in parsedOptions"
        :key="index"
    >
        <optgroup
            v-if="'options' in option"
            :label="option.label"
        >
            <select-options :options="option.options" />
        </optgroup>
        <option
            v-else
            :value="option.value"
        >
            {{ option.text }}
        </option>
    </template>
</template>

<script setup lang="ts">
import {toRef} from "vue";
import {NestedFormOptionInput, objectToNestedFormOptions} from "~/functions/objectToFormOptions.ts";

const props = defineProps<{
    options: NestedFormOptionInput
}>();

const parsedOptions = objectToNestedFormOptions(toRef(props, 'options'));
</script>
