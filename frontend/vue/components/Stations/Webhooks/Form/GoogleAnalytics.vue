<template>
    <b-tab :title="title">
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_tracking_id" :field="form.config.tracking_id">
                    <template #label>
                        <translate key="lang_form_config_tracking_id">GA Property Tracking ID</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_config_tracking_id_desc">The property ID used to track live listeners.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'GoogleAnalytics',
    components: {BWrappedFormGroup},
    props: {
        title: String,
        form: Object
    }
}
</script>
