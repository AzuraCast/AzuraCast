<template>
    <b-form-group>
        <div class="row g-3">
            <form-group-field
                id="form_config_api_secret"
                class="col-md-6"
                :field="form.config.api_secret"
            >
                <template #label>
                    {{ $gettext('Measurement Protocol API Secret') }}
                </template>
                <template #description>
                    {{ $gettext('This can be generated in the "Events" section for a measurement.') }}
                </template>
            </form-group-field>

            <form-group-field
                id="form_config_measurement_id"
                class="col-md-6"
                :field="form.config.measurement_id"
            >
                <template #label>
                    {{ $gettext('Measurement ID') }}
                </template>
                <template #description>
                    {{ $gettext('A unique identifier (i.e. "G-A1B2C3D4") for this measurement stream.') }}
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
