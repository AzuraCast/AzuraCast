<template>
    <b-form-group>
        <b-row>
            <b-wrapped-form-group
                v-for="field in customFields"
                :id="'edit_form_custom_'+field.short_name"
                :key="field.short_name"
                class="col-md-6"
                :field="form.custom_fields[field.short_name]"
            >
                <template #label>
                    {{ field.name }}
                </template>
            </b-wrapped-form-group>
        </b-row>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    customFields: {
        type: Array,
        required: true
    }
});
</script>
