<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_bot_token"
                class="col-md-6"
                :field="v$.config.bot_token"
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
                :field="v$.config.chat_id"
                :label="$gettext('Chat ID')"
                :description="$gettext('Unique identifier for the target chat or username of the target channel (in the format @channelusername).')"
            />

            <form-group-field
                id="form_config_api"
                class="col-md-6"
                :field="v$.config.api"
                :label="$gettext('Custom API Base URL')"
                :description="$gettext('Leave blank to use the default Telegram API URL (recommended).')"
            />
        </div>

        <common-formatting-info />

        <div class="row g-3">
            <form-group-field
                id="form_config_text"
                class="col-md-12"
                :field="v$.config.text"
                input-type="textarea"
                :label="$gettext('Main Message Content')"
            />

            <form-group-multi-check
                id="form_config_parse_mode"
                class="col-md-12"
                :field="v$.config.parse_mode"
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
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "./Common/FormattingInfo.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {$gettext} = useTranslate();

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        config: {
            bot_token: {required},
            chat_id: {required},
            api: {},
            text: {required},
            parse_mode: {required}
        }
    },
    form,
    () => {
        return {
            config: {
                bot_token: '',
                chat_id: '',
                api: '',
                text: $gettext(
                    'Now playing on %{ station }: %{ title } by %{ artist }! Tune in now.',
                    {
                        station: '{{ station.name }}',
                        title: '{{ now_playing.song.title }}',
                        artist: '{{ now_playing.song.artist }}'
                    }
                ),
                parse_mode: 'Markdown'
            }
        };
    }
);

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
