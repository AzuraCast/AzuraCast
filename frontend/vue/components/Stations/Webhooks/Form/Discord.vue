<template>
    <b-tab :title="title">
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_webhook_url" :field="form.config.webhook_url"
                                      input-type="url">
                    <template #label>
                        <translate key="lang_form_webhook_url">Discord Web Hook URL</translate>
                    </template>
                    <template #description>
                        <translate
                            key="lang_form_webhook_url">This URL is provided within the Discord application.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>

        <b-form-group>
            <template #label>
                <translate key="lang_customize_message_hdr">Customize Message</translate>
            </template>
            <template #description>
                <ul>
                    <li>
                        <translate key="lang_customize_message_desc_1">Variables are in the form of: </translate>
                        <code v-pre>{{ var.name }}</code>
                    </li>
                    <li>
                        <translate key="lang_customize_message_desc_2">All values in the NowPlaying API response are available for use. Any empty fields are ignored.</translate>
                    </li>
                    <li>
                        <a href="https://azuracast.com/api" target="_blank">
                            <translate key="lang_customize_response_link">NowPlaying API Response</translate>
                        </a>
                    </li>
                </ul>
            </template>

            <b-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_content" :field="form.config.content">
                    <template #label>
                        <translate key="lang_form_config_content">Main Message Content</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_title" :field="form.config.title">
                    <template #label>
                        <translate key="lang_form_config_title">Title</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_description" :field="form.config.description">
                    <template #label>
                        <translate key="lang_form_config_description">Description</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_url" :field="form.config.url" input-type="url">
                    <template #label>
                        <translate key="lang_form_config_url">URL</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_author" :field="form.config.author">
                    <template #label>
                        <translate key="lang_form_config_author">Author Name</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_thumbnail" :field="form.config.thumbnail"
                                      input-type="url">
                    <template #label>
                        <translate key="lang_form_config_thumbnail">Thumbnail Image URL</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_footer" :field="form.config.footer">
                    <template #label>
                        <translate key="lang_form_config_footer">Footer Text</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'Discord',
    components: {BWrappedFormGroup},
    props: {
        title: String,
        form: Object
    }
}
</script>
