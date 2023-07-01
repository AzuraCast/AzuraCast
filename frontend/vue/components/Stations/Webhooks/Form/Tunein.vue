<template>
    <b-form-group>
        <div class="row g-3">
            <form-group-field
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
            </form-group-field>

            <form-group-field
                id="form_config_partner_id"
                class="col-md-6"
                :field="form.config.partner_id"
            >
                <template #label>
                    {{ $gettext('TuneIn Partner ID') }}
                </template>
            </form-group-field>

            <form-group-field
                id="form_config_partner_key"
                class="col-md-6"
                :field="form.config.partner_key"
            >
                <template #label>
                    {{ $gettext('TuneIn Partner Key') }}
                </template>
            </form-group-field>
        </div>
    </b-form-group>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>
