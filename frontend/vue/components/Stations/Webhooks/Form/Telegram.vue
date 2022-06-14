<template>
    <div>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_bot_token" :field="form.config.bot_token">
                    <template #label="{lang}">
                        <translate :key="lang">Bot Token</translate>
                    </template>
                    <template #description="{lang}">
                        <a href="https://core.telegram.org/bots#botfather" target="_blank">
                            <translate :key="lang">See the Telegram Documentation for more details.</translate>
                        </a>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_chat_id" :field="form.config.chat_id">
                    <template #label="{lang}">
                        <translate :key="lang">Chat ID</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">Unique identifier for the target chat or username of the target channel (in the format @channelusername).</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_api" :field="form.config.api">
                    <template #label="{lang}">
                        <translate :key="lang">Custom API Base URL</translate>
                    </template>
                    <template #description="{lang}">
                        <translate
                            :key="lang">Leave blank to use the default Telegram API URL (recommended).</translate>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <common-formatting-info></common-formatting-info>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_text" :field="form.config.text"
                                      input-type="textarea">
                    <template #label="{lang}">
                        <translate :key="lang">Main Message Content</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_config_parse_mode" :field="form.config.parse_mode">
                    <template #label="{lang}">
                        <translate :key="lang">Message parsing mode</translate>
                    </template>
                    <template #description="{lang}">
                        <a href="https://core.telegram.org/bots/api#sendmessage" target="_blank">
                            <translate :key="lang">See the Telegram documentation for more details.</translate>
                        </a>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" :options="parseModeOptions"
                                            v-model="props.field.$model">
                        </b-form-radio-group>
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
    name: 'Telegram',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        parseModeOptions() {
            return [
                {
                    text: this.$gettext('Markdown'),
                    value: 'Markdown',
                },
                {
                    text: this.$gettext('HTML'),
                    value: 'HTML',
                }
            ];
        }
    }
}
</script>
