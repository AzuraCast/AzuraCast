<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-6" id="form_config_station_id" :field="form.config.station_id">
                <template #label="{lang}">
                    {{ $gettext('TuneIn Station ID') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('The station ID will be a numeric string that starts with the letter S.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_partner_id" :field="form.config.partner_id">
                <template #label="{lang}">
                    {{ $gettext('TuneIn Partner ID') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_partner_key" :field="form.config.partner_key">
                <template #label="{lang}">
                    {{ $gettext('TuneIn Partner Key') }}
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
