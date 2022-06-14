<template>
    <div>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_webhook_url" :field="form.config.webhook_url"
                                      input-type="url">
                    <template #label="{lang}">
                        <translate :key="lang">Discord Web Hook URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">This URL is provided within the Discord application.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <common-formatting-info></common-formatting-info>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_content" :field="form.config.content">
                    <template #label="{lang}">
                        <translate :key="lang">Main Message Content</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_title" :field="form.config.title">
                    <template #label="{lang}">
                        <translate :key="lang">Title</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_description" :field="form.config.description">
                    <template #label="{lang}">
                        <translate :key="lang">Description</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_url" :field="form.config.url" input-type="url">
                    <template #label="{lang}">
                        <translate :key="lang">URL</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_author" :field="form.config.author">
                    <template #label="{lang}">
                        <translate :key="lang">Author</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_thumbnail" :field="form.config.thumbnail"
                                      input-type="url">
                    <template #label="{lang}">
                        <translate :key="lang">Thumbnail Image URL</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_footer" :field="form.config.footer">
                    <template #label="{lang}">
                        <translate :key="lang">Footer Text</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./CommonFormattingInfo";

export default {
    name: 'Discord',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>
