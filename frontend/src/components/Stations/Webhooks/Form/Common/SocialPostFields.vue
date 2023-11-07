<template>
    <common-formatting-info />

    <div class="row g-3">
        <form-group-field
            v-if="hasTrigger('song_changed')"
            id="form_config_message"
            class="col-md-12"
            :field="v$.config.message"
            input-type="textarea"
            :label="$gettext('Message Body on Song Change')"
        />

        <form-group-field
            v-if="hasTrigger('song_changed_live')"
            id="form_config_message_song_changed_live"
            class="col-md-12"
            :field="v$.config.message_song_changed_live"
            input-type="textarea"
            :label="$gettext('Message Body on Song Change with Streamer/DJ Connected')"
        />

        <form-group-field
            v-if="hasTrigger('live_connect')"
            id="form_config_message_live_connect"
            class="col-md-12"
            :field="v$.config.message_live_connect"
            input-type="textarea"
            :label="$gettext('Message Body on Streamer/DJ Connect')"
        />

        <form-group-field
            v-if="hasTrigger('live_disconnect')"
            id="form_config_message_live_disconnect"
            class="col-md-12"
            :field="v$.config.message_live_disconnect"
            input-type="textarea"
            :label="$gettext('Message Body on Streamer/DJ Disconnect')"
        />

        <form-group-field
            v-if="hasTrigger('station_offline')"
            id="form_config_message_station_offline"
            class="col-md-12"
            :field="v$.config.message_station_offline"
            input-type="textarea"
            :label="$gettext('Message Body on Station Offline')"
        />

        <form-group-field
            v-if="hasTrigger('station_online')"
            id="form_config_message_station_online"
            class="col-md-12"
            :field="v$.config.message_station_online"
            input-type="textarea"
            :label="$gettext('Message Body on Station Online')"
        />
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import CommonFormattingInfo from "./FormattingInfo.vue";
import {includes} from 'lodash';
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {$gettext} = useTranslate();

const {v$} = useVuelidateOnFormTab(
    {
        config: {
            message: {},
            message_song_changed_live: {},
            message_live_connect: {},
            message_live_disconnect: {},
            message_station_offline: {},
            message_station_online: {}
        }
    },
    form,
    () => {
        return {
            config: {
                message: $gettext(
                    'Now playing on %{ station }: %{ title } by %{ artist }! Tune in now: %{ url }',
                    {
                        station: '{{ station.name }}',
                        title: '{{ now_playing.song.title }}',
                        artist: '{{ now_playing.song.artist }}',
                        url: '{{ station.public_player_url }}'
                    }
                ),
                message_song_changed_live: $gettext(
                    'Now playing on %{ station }: %{ title } by %{ artist } with your host, %{ dj }! Tune in now: %{ url }',
                    {
                        station: '{{ station.name }}',
                        title: '{{ now_playing.song.title }}',
                        artist: '{{ now_playing.song.artist }}',
                        dj: '{{ live.streamer_name }}',
                        url: '{{ station.public_player_url }}'
                    }
                ),
                message_live_connect: $gettext(
                    '%{ dj } is now live on %{ station }! Tune in now: %{ url }',
                    {
                        dj: '{{ live.streamer_name }}',
                        station: '{{ station.name }}',
                        url: '{{ station.public_player_url }}'
                    }
                ),
                message_live_disconnect: $gettext(
                    'Thanks for listening to %{ station }!',
                    {
                        station: '{{ station.name }}',
                    }
                ),
                message_station_offline: $gettext(
                    '%{ station } is going offline for now.',
                    {
                        station: '{{ station.name }}'
                    }
                ),
                message_station_online: $gettext(
                    '%{ station } is back online! Tune in now: %{ url }',
                    {
                        station: '{{ station.name }}',
                        url: '{{ station.public_player_url }}'
                    }
                )
            }
        }
    }
);

const hasTrigger = (trigger) => {
    return includes(props.form.triggers, trigger);
};
</script>
