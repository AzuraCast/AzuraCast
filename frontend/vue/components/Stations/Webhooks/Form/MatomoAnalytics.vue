<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_config_matomo_url" :field="form.config.matomo_url"
                                  input-type="url">
                <template #label="{lang}">
                    <translate :key="lang">Matomo Installation Base URL</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The full base URL of your Matomo installation.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_site_id" :field="form.config.site_id">
                <template #label="{lang}">
                    <translate :key="lang">Matomo Site ID</translate>
                </template>
                <template #description="{lang}">
                    <translate :key="lang">The numeric site ID for this site.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="form_config_token" :field="form.config.token">
                <template #label="{lang}">
                    <translate :key="lang">Matomo API Token</translate>
                </template>
                <template #description="{lang}">
                    <translate
                        :key="lang">Optionally supply an API token to allow IP address overriding.</translate>
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
