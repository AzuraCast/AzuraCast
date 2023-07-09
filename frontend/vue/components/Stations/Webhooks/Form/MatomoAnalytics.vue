<template>
    <div class="row g-3">
        <form-group-field
            id="form_config_matomo_url"
            class="col-md-12"
            :field="form.config.matomo_url"
            input-type="url"
            :label="$gettext('Matomo Installation Base URL')"
            :description="$gettext('The full base URL of your Matomo installation.')"
        />

        <form-group-field
            id="form_config_site_id"
            class="col-md-6"
            :field="form.config.site_id"
            :label="$gettext('Matomo Site ID')"
            :description="$gettext('The numeric site ID for this site.')"
        />

        <form-group-field
            id="form_config_token"
            class="col-md-6"
            :field="form.config.token"
            :label="$gettext('Matomo API Token')"
            :description="$gettext('Optionally supply an API token to allow IP address overriding.')"
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
