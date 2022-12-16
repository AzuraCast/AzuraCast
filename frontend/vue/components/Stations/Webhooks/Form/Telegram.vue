<template>
    <div>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_config_bot_token" :field="form.config.bot_token">
                    <template #label="{lang}">
                        {{ $gettext('Bot Token') }}
                    </template>
                    <template #description="{lang}">
                        <a href="https://core.telegram.org/bots#botfather" target="_blank">
                            {{ $gettext('See the Telegram Documentation for more details.') }}
                        </a>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_chat_id" :field="form.config.chat_id">
                    <template #label="{lang}">
                        {{ $gettext('Chat ID') }}
                    </template>
                    <template #description="{lang}">
                        {{
                            $gettext('Unique identifier for the target chat or username of the target channel (in the format @channelusername).')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_config_api" :field="form.config.api">
                    <template #label="{lang}">
                        {{ $gettext('Custom API Base URL') }}
                    </template>
                    <template #description="{lang}">
                        {{ $gettext('Leave blank to use the default Telegram API URL (recommended).') }}
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <common-formatting-info :now-playing-url="nowPlayingUrl"></common-formatting-info>

        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-12" id="form_config_text" :field="form.config.text"
                                      input-type="textarea">
                    <template #label="{lang}">
                        {{ $gettext('Main Message Content') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_config_parse_mode" :field="form.config.parse_mode">
                    <template #label="{lang}">
                        {{ $gettext('Message parsing mode') }}
                    </template>
                    <template #description="{lang}">
                        <a href="https://core.telegram.org/bots/api#sendmessage" target="_blank">
                            {{ $gettext('See the Telegram documentation for more details.') }}
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
import CommonFormattingInfo from "./Common/FormattingInfo";

export default {
    name: 'Telegram',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object,
        nowPlayingUrl: String
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
