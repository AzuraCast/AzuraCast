<template>
    <b-form-group>
        <div class="row g-3">
            <form-group-field
                id="form_config_tracking_id"
                class="col-md-12"
                :field="form.config.tracking_id"
            >
                <template #label>
                    {{ $gettext('GA Property Tracking ID') }}
                </template>
                <template #description>
                    {{ $gettext('The property ID used to track live listeners.') }}
                </template>
            </form-group-field>
        </div>
    </b-form-group>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>
