<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="form_config_tracking_id" :field="form.config.tracking_id">
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

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'GoogleAnalytics',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>
