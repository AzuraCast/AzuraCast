<template>
    <template
        v-for="(option, index) in parsedOptions"
        :key="index"
    >
        <optgroup
            v-if="Array.isArray(option.options)"
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
import objectToFormOptions, {FormOptionInput} from "~/functions/objectToFormOptions";

const props = defineProps<{
    options: FormOptionInput
}>();

const parsedOptions = objectToFormOptions(toRef(props, 'options'));
</script>
