<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
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
