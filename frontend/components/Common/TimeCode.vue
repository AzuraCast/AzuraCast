<template>
    <input
        v-bind="$attrs"
        v-model="timeCode"
        class="form-control"
        type="time"
        pattern="[0-9]{2}:[0-9]{2}"
        placeholder="13:45"
    >
</template>

<script setup lang="ts">
import {computed} from "vue";
import {isEmpty, padStart} from 'lodash';

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null
    }>(),
    {
        modelValue: null,
    }
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void
}>();

const parseTimeCode = (timeCode: string | number | null) => {
    if (timeCode !== '' && timeCode !== null) {
        timeCode = padStart(String(timeCode), 4, '0');
        return timeCode.substring(0, 2) + ':' + timeCode.substring(2);
    }

    return null;
}

const convertToTimeCode = (time: string): number | null => {
    if (isEmpty(time)) {
        return null;
    }

    const timeParts = time.split(':');
    return (100 * parseInt(timeParts[0], 10)) + parseInt(timeParts[1], 10);
}

const timeCode = computed({
    get: () => {
        return parseTimeCode(props.modelValue);
    },
    set: (newValue) => {
        emit('update:modelValue', convertToTimeCode(newValue));
    }
});
</script>
