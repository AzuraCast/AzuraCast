<template>
    <div class="row g-3">
        <form-group-field
            id="form_config_api_secret"
            class="col-md-6"
            :field="form.config.api_secret"
            :label="$gettext('Measurement Protocol API Secret')"
            :description="$gettext('This can be generated in the &quot;Events&quot; section for a measurement.')"
        />

        <form-group-field
            id="form_config_measurement_id"
            class="col-md-6"
            :field="form.config.measurement_id"
            :label="$gettext('Measurement ID')"
            :description="$gettext('A unique identifier (i.e. &quot;G-A1B2C3D4&quot;) for this measurement stream.')"
        />
    </div>
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
