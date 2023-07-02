<template>
    <template
        v-for="(option, index) in options"
        :key="index"
    >
        <optgroup
            v-if="Array.isArray(option.options)"
            :label="option.label"
        >
            <form-group-select-option :options="option.options" />
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
const props = defineProps({
    options: {
        type: Array,
        required: true
    }
});
</script>
