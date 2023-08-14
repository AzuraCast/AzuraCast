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

<script setup>
import {computed} from "vue";
import objectToFormOptions from "~/functions/objectToFormOptions";

const props = defineProps({
    options: {
        type: Array,
        required: true
    }
});

const parsedOptions = computed(() => {
    if (Array.isArray(props.options)) {
        return props.options;
    }

    return objectToFormOptions(props.options);
});
</script>
