<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_config_tracking_id" :field="form.config.tracking_id">
                <template #label="{lang}">
                    <translate :key="lang">GA Property Tracking ID</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The property ID used to track live listeners.</translate>
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
