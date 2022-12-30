<template>
    <b-input
        v-bind="$attrs"
        v-model="timeCode"
        type="time"
        pattern="[0-9]{2}:[0-9]{2}"
        placeholder="13:45"
    />
</template>

<script setup>

import {computed} from "vue";
import {isEmpty, padStart} from 'lodash';

const props = defineProps({
    modelValue: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:modelValue']);

const parseTimeCode = (timeCode) => {
    if (timeCode !== '' && timeCode !== null) {
        timeCode = padStart(timeCode, 4, '0');
        return timeCode.substring(0, 2) + ':' + timeCode.substring(2);
    }

    return null;
}

const convertToTimeCode = (time) => {
    if (isEmpty(time)) {
        return null;
    }

    let timeParts = time.split(':');
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

<script>
export default {
    model: {
        prop: 'modelValue',
        event: 'update:modelValue'
    },
};
</script>
