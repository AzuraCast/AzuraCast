<template>
    <div>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_to" :field="form.config.to">
                    <template #label="{lang}">
                        <translate :key="lang">Message Recipient(s)</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">E-mail addresses can be separated by commas.</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <common-formatting-info></common-formatting-info>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_subject" :field="form.config.subject">
                    <template #label="{lang}">
                        <translate :key="lang">Message Subject</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_config_message" :field="form.config.message">
                    <template #label="{lang}">
                        <translate :key="lang">Message Body</translate>
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
    name: 'Email',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object
    }
}
</script>
