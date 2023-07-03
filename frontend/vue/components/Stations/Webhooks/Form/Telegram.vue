<template>
    <div class="row g-3 mb-3">
        <form-group-field
            id="form_config_bot_token"
            class="col-md-6"
            :field="form.config.bot_token"
            :label="$gettext('Bot Token')"
        >
            <template #description>
                <a
                    href="https://core.telegram.org/bots#botfather"
                    target="_blank"
                >
                    {{ $gettext('See the Telegram Documentation for more details.') }}
                </a>
            </template>
        </form-group-field>

        <form-group-field
            id="form_config_chat_id"
            class="col-md-6"
            :field="form.config.chat_id"
            :label="$gettext('Chat ID')"
            :description="$gettext('Unique identifier for the target chat or username of the target channel (in the format @channelusername).')"
        />

        <form-group-field
            id="form_config_api"
            class="col-md-6"
            :field="form.config.api"
            :label="$gettext('Custom API Base URL')"
            :description="$gettext('Leave blank to use the default Telegram API URL (recommended).')"
        />
    </div>

    <common-formatting-info :now-playing-url="nowPlayingUrl" />

    <div class="row g-3">
        <form-group-field
            id="form_config_text"
            class="col-md-12"
            :field="form.config.text"
            input-type="textarea"
            :label="$gettext('Main Message Content')"
        />

        <form-group-multi-check
            id="form_config_parse_mode"
            class="col-md-12"
            :field="form.config.parse_mode"
            :options="parseModeOptions"
            stacked
            radio
            :label="$gettext('Message parsing mode')"
        >
            <template #description>
                <a
                    href="https://core.telegram.org/bots/api#sendmessage"
                    target="_blank"
                >
                    {{ $gettext('See the Telegram documentation for more details.') }}
                </a>
            </template>
        </form-group-multi-check>
    </div>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import CommonFormattingInfo from "./Common/FormattingInfo";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

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
