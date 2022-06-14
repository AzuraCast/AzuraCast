<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="form_config_station_id" :field="form.config.station_id">
                <template #label="{lang}">
                    <translate :key="lang">TuneIn Station ID</translate>
                </template>
                <template #description="{lang}">
                    <translate
                        :key="lang">The station ID will be a numeric string that starts with the letter S.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_partner_id" :field="form.config.partner_id">
                <template #label="{lang}">
                    <translate :key="lang">TuneIn Partner ID</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_partner_key" :field="form.config.partner_key">
                <template #label="{lang}">
                    <translate :key="lang">TuneIn Partner Key</translate>
                </template>
            </b-wrapped-form-group>
        </b-form-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'Tunein',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>
