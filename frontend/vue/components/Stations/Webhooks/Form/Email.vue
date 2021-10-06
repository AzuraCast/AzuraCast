<template>
    <b-tab :title="title">
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_to" :field="form.config.to">
                    <template #label>
                        <translate key="lang_form_to">Message Recipient(s)</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_to_desc">E-mail addresses can be separated by commas.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>

        <common-formatting-info></common-formatting-info>

        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_subject" :field="form.config.subject">
                    <template #label>
                        <translate key="lang_form_config_subject">Message Subject</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_config_message" :field="form.config.message">
                    <template #label>
                        <translate key="lang_form_config_message">Message Body</translate>
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
    name: 'Email',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        title: String,
        form: Object
    }
}
</script>
