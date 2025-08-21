<template>
    <common-formatting-info />

    <div class="row g-3">
        <form-group-field
            v-if="hasTrigger('song_changed')"
            id="form_config_message"
            class="col-md-12"
            :field="r$.config.message"
            input-type="textarea"
            :label="$gettext('Message Body on Song Change')"
        />

        <form-group-field
            v-if="hasTrigger('song_changed_live')"
            id="form_config_message_song_changed_live"
            class="col-md-12"
            :field="r$.config.message_song_changed_live"
            input-type="textarea"
            :label="$gettext('Message Body on Song Change with Streamer/DJ Connected')"
        />

        <form-group-field
            v-if="hasTrigger('live_connect')"
            id="form_config_message_live_connect"
            class="col-md-12"
            :field="r$.config.message_live_connect"
            input-type="textarea"
            :label="$gettext('Message Body on Streamer/DJ Connect')"
        />

        <form-group-field
            v-if="hasTrigger('live_disconnect')"
            id="form_config_message_live_disconnect"
            class="col-md-12"
            :field="r$.config.message_live_disconnect"
            input-type="textarea"
            :label="$gettext('Message Body on Streamer/DJ Disconnect')"
        />

        <form-group-field
            v-if="hasTrigger('station_offline')"
            id="form_config_message_station_offline"
            class="col-md-12"
            :field="r$.config.message_station_offline"
            input-type="textarea"
            :label="$gettext('Message Body on Station Offline')"
        />

        <form-group-field
            v-if="hasTrigger('station_online')"
            id="form_config_message_station_online"
            class="col-md-12"
            :field="r$.config.message_station_online"
            input-type="textarea"
            :label="$gettext('Message Body on Station Online')"
        />
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "~/components/Stations/Webhooks/Form/Common/FormattingInfo.vue";
import {includes} from "lodash";
import {WebhookRecordCommon, WebhookRecordCommonMessages} from "~/components/Stations/Webhooks/Form/form.ts";
import {useAppScopedRegle} from "~/vendor/regle.ts";

type FormWithSocialFields = WebhookRecordCommon & {
    config: WebhookRecordCommonMessages
};

const form = defineModel<FormWithSocialFields>('form', {required: true});

const {r$} = useAppScopedRegle(
    form,
    {},
    {
        namespace: 'station-webhooks'
    }
);

const hasTrigger = (trigger) => {
    return includes(form.value.triggers, trigger);
};
</script>
