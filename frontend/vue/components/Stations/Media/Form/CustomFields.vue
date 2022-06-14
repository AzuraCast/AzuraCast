<template>
    <b-form-group>
        <b-row>
            <b-wrapped-form-group v-for="field in customFields" :key="field.short_name" class="col-md-6"
                                  :id="'edit_form_custom_'+field.short_name"
                                  :field="form.custom_fields[field.short_name]">
                <template #label>{{ field.name }}</template>
            </b-wrapped-form-group>
        </b-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'MediaFormCustomFields',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        customFields: Array
    },
};
</script>
