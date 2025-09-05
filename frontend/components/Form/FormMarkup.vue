<template>
    <form-group :id="id">
        <template
            v-if="label || slots.label"
            #label="slotProps"
        >
            <slot
                name="label"
                v-bind="slotProps"
            >
                {{ label }}
            </slot>
        </template>

        <template #default="slotProps">
            <div :id="id">
                <slot
                    v-bind="slotProps"
                    :id="id"
                    name="default"
                />
            </div>
        </template>

        <template
            v-if="description || slots.description"
            #description="slotProps"
        >
            <slot
                v-bind="slotProps"
                name="description"
            >
                {{ description }}
            </slot>
        </template>
    </form-group>
</template>

<script setup lang="ts">
import FormGroup from "~/components/Form/FormGroup.vue";
import {useSlots} from "vue";

withDefaults(
    defineProps<{
        id: string,
        label?: string,
        description?: string
    }>(),
    {
        label: '',
        description: ''
    }
);

const slots = useSlots();
</script>
