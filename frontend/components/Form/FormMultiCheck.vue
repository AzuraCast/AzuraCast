<template>
    <div>
        <div
            v-for="option in parsedOptions"
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
                <slot :name="'label('+option.value+')'">
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
import {objectToSimpleFormOptions, SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {ModelFormField} from "~/components/Form/useFormField.ts";
import {toRef} from "vue";

const props = withDefaults(
    defineProps<{
        modelValue?: ModelFormField,
        id: string,
        name?: string,
        fieldClass?: string,
        options: SimpleFormOptionInput,
        radio?: boolean,
        stacked?: boolean
    }>(),
    {
        modelValue: null,
        name: (props) => props.id,
        fieldClass: null,
        radio: false,
        stacked: false,
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', modelValue: ModelFormField): void
}>();

const value = useVModel(props, 'modelValue', emit);

const parsedOptions = objectToSimpleFormOptions(toRef(props, 'options'));
</script>
