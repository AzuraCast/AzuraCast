<template>
    <b-tab :title="title">
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_matomo_url" :field="form.config.matomo_url"
                                      input-type="url">
                    <template #label>
                        <translate key="lang_form_config_matomo_url">Matomo Installation Base URL</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_config_matomo_url_desc">The full base URL of your Matomo installation.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_site_id" :field="form.config.site_id">
                    <template #label>
                        <translate key="lang_form_config_site_id">Matomo Site ID</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_config_site_id_desc">The numeric site ID for this site.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_token" :field="form.config.token">
                    <template #label>
                        <translate key="lang_form_config_token">Matomo API Token</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_config_site_id_desc">Optionally supply an API token to allow IP address overriding.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'MatomoAnalytics',
    components: {BWrappedFormGroup},
    props: {
        title: String,
        form: Object
    }
}
</script>
