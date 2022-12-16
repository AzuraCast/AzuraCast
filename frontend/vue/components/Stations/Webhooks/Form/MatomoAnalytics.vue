<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_config_matomo_url" :field="form.config.matomo_url"
                                  input-type="url">
                <template #label="{lang}">
                    {{ $gettext('Matomo Installation Base URL') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('The full base URL of your Matomo installation.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_site_id" :field="form.config.site_id">
                <template #label="{lang}">
                    {{ $gettext('Matomo Site ID') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('The numeric site ID for this site.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_token" :field="form.config.token">
                <template #label="{lang}">
                    {{ $gettext('Matomo API Token') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('Optionally supply an API token to allow IP address overriding.') }}
                </template>
            </b-wrapped-form-group>
        </b-form-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'MatomoAnalytics',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>
