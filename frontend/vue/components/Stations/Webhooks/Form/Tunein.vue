<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_station_id"
                class="col-md-6"
                :field="form.config.station_id"
            >
                <template #label>
                    {{ $gettext('TuneIn Station ID') }}
                </template>
                <template #description>
                    {{ $gettext('The station ID will be a numeric string that starts with the letter S.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_partner_id"
                class="col-md-6"
                :field="form.config.partner_id"
            >
                <template #label>
                    {{ $gettext('TuneIn Partner ID') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_partner_key"
                class="col-md-6"
                :field="form.config.partner_key"
            >
                <template #label>
                    {{ $gettext('TuneIn Partner Key') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

const props = defineProps({
    form: Object
});
</script>
