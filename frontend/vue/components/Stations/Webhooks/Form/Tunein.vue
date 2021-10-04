<template>
    <b-tab :title="title">
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_station_id" :field="form.config.station_id">
                    <template #label>
                        <translate key="lang_form_station_id">TuneIn Station ID</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_station_id_desc">The station ID will be a numeric string that starts with the letter S.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_partner_id" :field="form.config.partner_id">
                    <template #label>
                        <translate key="lang_form_partner_id">TuneIn Partner ID</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_partner_key" :field="form.config.partner_key">
                    <template #label>
                        <translate key="lang_form_partner_key">TuneIn Partner Key</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'Tunein',
    components: {BWrappedFormGroup},
    props: {
        title: String,
        form: Object
    }
}
</script>
