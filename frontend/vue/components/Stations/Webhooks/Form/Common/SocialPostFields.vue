<template>
    <div>
        <common-formatting-info :now-playing-url="nowPlayingUrl" />

        <b-form-group>
            <div class="form-row">
                <template v-if="hasTrigger('song_changed')">
                    <b-wrapped-form-group
                        id="form_config_message"
                        class="col-md-12"
                        :field="form.config.message"
                        input-type="textarea"
                    >
                        <template #label>
                            {{ $gettext('Message Body on Song Change') }}
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('song_changed_live')">
                    <b-wrapped-form-group
                        id="form_config_message_song_changed_live"
                        class="col-md-12"
                        :field="form.config.message_song_changed_live"
                        input-type="textarea"
                    >
                        <template #label>
                            {{ $gettext('Message Body on Song Change with Stramer/DJ Connected') }}
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('live_connect')">
                    <b-wrapped-form-group
                        id="form_config_message_live_connect"
                        class="col-md-12"
                        :field="form.config.message_live_connect"
                        input-type="textarea"
                    >
                        <template #label>
                            {{ $gettext('Message Body on Streamer/DJ Connect') }}
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('live_disconnect')">
                    <b-wrapped-form-group
                        id="form_config_message_live_disconnect"
                        class="col-md-12"
                        :field="form.config.message_live_disconnect"
                        input-type="textarea"
                    >
                        <template #label>
                            {{ $gettext('Message Body on Streamer/DJ Disconnect') }}
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('station_offline')">
                    <b-wrapped-form-group
                        id="form_config_message_station_offline"
                        class="col-md-12"
                        :field="form.config.message_station_offline"
                        input-type="textarea"
                    >
                        <template #label>
                            {{ $gettext('Message Body on Station Offline') }}
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('station_online')">
                    <b-wrapped-form-group
                        id="form_config_message_station_online"
                        class="col-md-12"
                        :field="form.config.message_station_online"
                        input-type="textarea"
                    >
                        <template #label>
                            {{ $gettext('Message Body on Station Online') }}
                        </template>
                    </b-wrapped-form-group>
                </template>
            </div>
        </b-form-group>
    </div>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./FormattingInfo";
import {includes} from 'lodash';

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

const hasTrigger = (trigger) => {
    return includes(props.form.triggers.$model, trigger);
};
</script>
