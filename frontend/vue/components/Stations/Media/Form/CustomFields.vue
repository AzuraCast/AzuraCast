<template>
    <b-form-group>
        <b-row>
            <form-group-field
                v-for="field in customFields"
                :id="'edit_form_custom_'+field.short_name"
                :key="field.short_name"
                class="col-md-6"
                :field="form.custom_fields[field.short_name]"
            >
                <template #label>
                    {{ field.name }}
                </template>
            </form-group-field>
        </b-row>
    </b-form-group>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";

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
