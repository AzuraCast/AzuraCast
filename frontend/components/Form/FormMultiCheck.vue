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

<script setup lang="ts" generic="T = ModelFormField">
import {objectToSimpleFormOptions, SimpleFormOptionInput} from "~/functions/objectToFormOptions.ts";
import {ModelFormField} from "~/components/Form/useFormField.ts";
import {toRef} from "vue";

const props = withDefaults(
    defineProps<{
        id: string,
        name?: string,
        fieldClass?: string,
        options: SimpleFormOptionInput,
        radio?: boolean,
        stacked?: boolean
    }>(),
    {
        name: (props) => props.id,
        radio: false,
        stacked: false,
    }
)

const value = defineModel<T>({
    default: null
});

const parsedOptions = objectToSimpleFormOptions(toRef(props, 'options'));
</script>
