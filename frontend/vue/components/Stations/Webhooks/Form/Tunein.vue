<template>
    <div class="row g-3">
        <form-group-field
            id="form_config_station_id"
            class="col-md-6"
            :field="form.config.station_id"
            :label="$gettext('TuneIn Station ID')"
            :description="$gettext('The station ID will be a numeric string that starts with the letter S.')"
        />

        <form-group-field
            id="form_config_partner_id"
            class="col-md-6"
            :field="form.config.partner_id"
            :label="$gettext('TuneIn Partner ID')"
        />

        <form-group-field
            id="form_config_partner_key"
            class="col-md-6"
            :field="form.config.partner_key"
            :label="$gettext('TuneIn Partner Key')"
        />
    </div>
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
