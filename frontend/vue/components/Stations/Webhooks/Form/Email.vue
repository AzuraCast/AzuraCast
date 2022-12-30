<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_to"
                class="col-md-12"
                :field="form.config.to"
            >
                <template #label>
                    {{ $gettext('Message Recipient(s)') }}
                </template>
                <template #description>
                    {{ $gettext('E-mail addresses can be separated by commas.') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>

    <common-formatting-info :now-playing-url="nowPlayingUrl" />

    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_subject"
                class="col-md-12"
                :field="form.config.subject"
            >
                <template #label>
                    {{ $gettext('Message Subject') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_message"
                class="col-md-12"
                :field="form.config.message"
            >
                <template #label>
                    {{ $gettext('Message Body') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
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
