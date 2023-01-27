<template>
    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_bot_token"
                class="col-md-6"
                :field="form.config.bot_token"
            >
                <template #label>
                    {{ $gettext('Bot Token') }}
                </template>
                <template #description>
                    <a
                        href="https://core.telegram.org/bots#botfather"
                        target="_blank"
                    >
                        {{ $gettext('See the Telegram Documentation for more details.') }}
                    </a>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_chat_id"
                class="col-md-6"
                :field="form.config.chat_id"
            >
                <template #label>
                    {{ $gettext('Chat ID') }}
                </template>
                <template #description>
                    {{
                        $gettext('Unique identifier for the target chat or username of the target channel (in the format @channelusername).')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_api"
                class="col-md-6"
                :field="form.config.api"
            >
                <template #label>
                    {{ $gettext('Custom API Base URL') }}
                </template>
                <template #description>
                    {{ $gettext('Leave blank to use the default Telegram API URL (recommended).') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>

    <common-formatting-info :now-playing-url="nowPlayingUrl" />

    <b-form-group>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_config_text"
                class="col-md-12"
                :field="form.config.text"
                input-type="textarea"
            >
                <template #label>
                    {{ $gettext('Main Message Content') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_config_parse_mode"
                class="col-md-12"
                :field="form.config.parse_mode"
            >
                <template #label>
                    {{ $gettext('Message parsing mode') }}
                </template>
                <template #description>
                    <a
                        href="https://core.telegram.org/bots/api#sendmessage"
                        target="_blank"
                    >
                        {{ $gettext('See the Telegram documentation for more details.') }}
                    </a>
                </template>
                <template #default="slotProps">
                    <b-form-radio-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                        :options="parseModeOptions"
                    />
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./Common/FormattingInfo";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

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

const {$gettext} = useTranslate();

const parseModeOptions = computed(() => {
    return [
        {
            text: $gettext('Markdown'),
            value: 'Markdown',
        },
        {
            text: $gettext('HTML'),
            value: 'HTML',
        }
    ];
});
</script>
