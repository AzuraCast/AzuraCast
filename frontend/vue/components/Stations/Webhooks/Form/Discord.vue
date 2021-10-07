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

        <common-formatting-info></common-formatting-info>

        <b-form-group>
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
import CommonFormattingInfo from "./CommonFormattingInfo";

export default {
    name: 'Discord',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        title: String,
        form: Object
    }
}
</script>
