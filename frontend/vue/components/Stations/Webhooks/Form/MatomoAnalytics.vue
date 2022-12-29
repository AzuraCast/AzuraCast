<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_matomo_url"
                class="col-md-12"
                :field="form.config.matomo_url"
                input-type="url"
            >
                <template #label>
                    {{ $gettext('Matomo Installation Base URL') }}
                </template>
                <template #description>
                    {{ $gettext('The full base URL of your Matomo installation.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_site_id"
                class="col-md-6"
                :field="form.config.site_id"
            >
                <template #label>
                    {{ $gettext('Matomo Site ID') }}
                </template>
                <template #description>
                    {{ $gettext('The numeric site ID for this site.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_token"
                class="col-md-6"
                :field="form.config.token"
            >
                <template #label>
                    {{ $gettext('Matomo API Token') }}
                </template>
                <template #description>
                    {{ $gettext('Optionally supply an API token to allow IP address overriding.') }}
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
