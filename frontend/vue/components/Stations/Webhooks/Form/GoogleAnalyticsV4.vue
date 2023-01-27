<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
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
            </b-wrapped-form-group>

            <b-wrapped-form-group
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
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>
