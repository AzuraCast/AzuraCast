<template>
    <div class="row g-3 mb-3">
        <form-group-field
            id="form_config_webhook_url"
            class="col-md-12"
            :field="form.config.webhook_url"
            input-type="url"
            :label="$gettext('Discord Web Hook URL')"
            :description="$gettext('This URL is provided within the Discord application.')"
        />
    </div>

    <common-formatting-info :now-playing-url="nowPlayingUrl" />

    <div class="row g-3">
        <form-group-field
            id="form_config_content"
            class="col-md-6"
            :field="form.config.content"
            input-type="textarea"
            :label="$gettext('Main Message Content')"
        />

        <form-group-field
            id="form_config_title"
            class="col-md-6"
            :field="form.config.title"
            :label="$gettext('Title')"
        />

        <form-group-field
            id="form_config_description"
            class="col-md-6"
            :field="form.config.description"
            input-type="textarea"
            :label="$gettext('Description')"
        />

        <form-group-field
            id="form_config_url"
            class="col-md-6"
            :field="form.config.url"
            input-type="url"
            :label="$gettext('URL')"
        />

        <form-group-field
            id="form_config_author"
            class="col-md-6"
            :field="form.config.author"
            :label="$gettext('Author')"
        />

        <form-group-field
            id="form_config_thumbnail"
            class="col-md-6"
            :field="form.config.thumbnail"
            input-type="url"
            :label="$gettext('Thumbnail Image URL')"
        />

        <form-group-field
            id="form_config_footer"
            class="col-md-6"
            :field="form.config.footer"
            :label="$gettext('Footer Text')"
        />
    </div>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import CommonFormattingInfo from "./Common/FormattingInfo";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    nowPlayingUrl: {
        type: String,
        required: true
    }
});
</script>
