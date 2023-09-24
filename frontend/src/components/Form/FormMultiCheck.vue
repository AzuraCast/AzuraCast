<template>
    <div>
        <div
            v-for="option in options"
            :key="option.value"
            class="form-check"
            :class="!stacked ? 'form-check-inline' : ''"
        >
            <input
                :id="id+'_'+option.value"
                v-model="value"
                :value="option.value"
                class="form-check-input"
                :class="fieldClass"
                :type="radio ? 'radio' : 'checkbox'"
                :name="name"
            >
            <label
                class="form-check-label"
                :for="id+'_'+option.value"
            >
                <slot :name="`label(${option.value})`">
                    <template v-if="option.description">
                        <strong>{{ option.text }}</strong>
                        <br>
                        <small>{{ option.description }}</small>
                    </template>
                    <template v-else>
                        {{ option.text }}
                    </template>
                </slot>
            </label>
        </div>
    </div>
</template>

<script setup lang="ts">
import {useVModel} from "@vueuse/core";

const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean, Array],
        required: true
    },
    id: {
        type: String,
        required: true
    },
    name: {
        type: String,
        default: null
    },
    fieldClass: {
        type: String,
        default: null,
    },
    options: {
        type: Array<any>,
        required: true
    },
    radio: {
        type: Boolean,
        default: false
    },
    stacked: {
        type: Boolean,
        default: false
    },
});

const emit = defineEmits(['update:modelValue']);

const value = useVModel(props, 'modelValue', emit);
</script>
