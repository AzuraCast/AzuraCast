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

export default {
    name: 'Email',
    components: {BWrappedFormGroup},
    props: {
        title: String,
        form: Object
    }
}
</script>
