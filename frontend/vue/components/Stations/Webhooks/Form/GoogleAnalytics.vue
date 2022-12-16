<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_config_tracking_id" :field="form.config.tracking_id">
                <template #label="{lang}">
                    {{ $gettext('GA Property Tracking ID') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('The property ID used to track live listeners.') }}
                </template>
            </b-wrapped-form-group>
        </b-form-row>
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
