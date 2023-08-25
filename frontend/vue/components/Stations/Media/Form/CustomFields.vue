<template>
    <div class="row">
        <form-group-field
            v-for="field in customFields"
            :id="'edit_form_custom_'+field.short_name"
            :key="field.short_name"
            class="col-md-6"
            :field="form.custom_fields[field.short_name]"
            :label="field.name"
        />
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";

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
