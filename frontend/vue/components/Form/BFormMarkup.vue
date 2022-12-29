<template>
    <b-form-group
        v-bind="$attrs"
        :label-for="id"
    >
        <template #default="slotProps">
            <div :id="id">
                <slot
                    name="default"
                    v-bind="slotProps"
                />
            </div>
        </template>

        <template #label="slotProps">
            <slot
                name="label"
                v-bind="slotProps"
            />
        </template>
        <template #description="slotProps">
            <slot
                name="description"
                v-bind="slotProps"
            />
        </template>

        <template
            v-for="(_, slot) of filteredSlots"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope"
            />
        </template>
    </b-form-group>
</template>

<script setup>
import useSlotsExcept from "~/functions/useSlotsExcept";

const props = defineProps({
    id: {
        type: String,
        required: true
    }
});

const filteredSlots = useSlotsExcept(['default', 'label', 'description']);
</script>
