<template>
    <div class="row g-3 mb-3">
        <form-group-field
            id="form_config_to"
            class="col-md-12"
            :field="form.config.to"
            :label="$gettext('Message Recipient(s)')"
            :description="$gettext('E-mail addresses can be separated by commas.')"
        />
    </div>

    <common-formatting-info :now-playing-url="nowPlayingUrl" />

    <div class="row g-3">
        <form-group-field
            id="form_config_subject"
            class="col-md-12"
            :field="form.config.subject"
            :label="$gettext('Message Subject')"
        />

        <form-group-field
            id="form_config_message"
            class="col-md-12"
            :field="form.config.message"
            :label="$gettext('Message Body')"
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
