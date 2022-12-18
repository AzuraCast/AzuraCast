<template>
    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_config_to" :field="form.config.to">
                <template #label="{lang}">
                    {{ $gettext('Message Recipient(s)') }}
                </template>
                <template #description="{lang}">
                    {{ $gettext('E-mail addresses can be separated by commas.') }}
                </template>
            </b-wrapped-form-group>
        </b-form-row>
    </b-form-group>

    <common-formatting-info :now-playing-url="nowPlayingUrl"></common-formatting-info>

    <b-form-group>
        <b-form-row>
            <b-wrapped-form-group class="col-md-12" id="form_config_subject" :field="form.config.subject">
                <template #label="{lang}">
                    {{ $gettext('Message Subject') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="form_config_message" :field="form.config.message">
                <template #label="{lang}">
                    {{ $gettext('Message Body') }}
                </template>
            </b-wrapped-form-group>
        </b-form-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./Common/FormattingInfo";

export default {
    name: 'Email',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object,
        nowPlayingUrl: String
    }
}
</script>
