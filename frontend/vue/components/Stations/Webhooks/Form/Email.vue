<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="form_config_to" :field="form.config.to">
                <template #label>
                    {{ $gettext('Message Recipient(s)') }}
                </template>
                <template #description>
                    {{ $gettext('E-mail addresses can be separated by commas.') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>

    <common-formatting-info :now-playing-url="nowPlayingUrl"></common-formatting-info>

    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="form_config_subject" :field="form.config.subject">
                <template #label>
                    {{ $gettext('Message Subject') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="form_config_message" :field="form.config.message">
                <template #label>
                    {{ $gettext('Message Body') }}
                </template>
            </b-wrapped-form-group>
        </div>
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
