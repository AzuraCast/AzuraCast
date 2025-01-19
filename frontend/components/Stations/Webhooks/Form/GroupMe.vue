<template>
    <tab
        :label="title"
        :item-header-class="tabClass"
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_config_bot_id"
                class="col-md-6"
                :field="v$.config.bot_id"
                :label="$gettext('Bot ID')"
            >
                <template #description>
                    <a
                        href="https://dev.groupme.com/tutorials/bots"
                        target="_blank"
                    >
                        {{ $gettext('See the GroupMe Documentation for more details.') }}
                    </a>
                </template>
            </form-group-field>

            <form-group-field
                id="form_config_api"
                class="col-md-6"
                :field="v$.config.api"
                :label="$gettext('Custom API Base URL')"
                :description="$gettext('Leave blank to use the default GroupMe API URL (recommended).')"
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
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "./Common/FormattingInfo.vue";
import {useTranslate} from "~/vendor/gettext";
import {FormTabEmits, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";
import {WebhookComponentProps} from "~/components/Stations/Webhooks/EditModal.vue";

const props = defineProps<WebhookComponentProps>();
const emit = defineEmits<FormTabEmits>();

const { $gettext } = useTranslate();

const { v$, tabClass } = useVuelidateOnFormTab(
    props,
    emit,
    {
        config: {
            bot_id: { required },
            api: {},
            text: { required }
        }
    },
    () => ({
        config: {
            bot_id: '',
            api: '',
            text: $gettext(
                'Now playing on %{station}: %{title} by %{artist}! Tune in now.',
                {
                    station: '{{ station.name }}',
                    title: '{{ now_playing.song.title }}',
                    artist: '{{ now_playing.song.artist }}'
                }
            )
        }
    })
);

</script>
