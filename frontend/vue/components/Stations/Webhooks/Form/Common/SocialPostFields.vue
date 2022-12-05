<template>
    <div>
        <common-formatting-info :now-playing-url="nowPlayingUrl"></common-formatting-info>

        <b-form-group>
            <b-form-row>
                <template v-if="hasTrigger('song_changed')">
                    <b-wrapped-form-group class="col-md-12" id="form_config_message" :field="form.config.message"
                                          input-type="textarea">
                        <template #label="{lang}">
                            <translate :key="lang">Message Body on Song Change</translate>
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('song_changed_live')">
                    <b-wrapped-form-group class="col-md-12" id="form_config_message_song_changed_live"
                                          :field="form.config.message_song_changed_live"
                                          input-type="textarea">
                        <template #label="{lang}">
                            <translate :key="lang">Message Body on Song Change with Stramer/DJ Connected</translate>
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('live_connect')">
                    <b-wrapped-form-group class="col-md-12" id="form_config_message_live_connect"
                                          :field="form.config.message_live_connect" input-type="textarea">
                        <template #label="{lang}">
                            <translate :key="lang">Message Body on Streamer/DJ Connect</translate>
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('live_disconnect')">
                    <b-wrapped-form-group class="col-md-12" id="form_config_message_live_disconnect"
                                          :field="form.config.message_live_disconnect" input-type="textarea">
                        <template #label="{lang}">
                            <translate :key="lang">Message Body on Streamer/DJ Disconnect</translate>
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('station_offline')">
                    <b-wrapped-form-group class="col-md-12" id="form_config_message_station_offline"
                                          :field="form.config.message_station_offline" input-type="textarea">
                        <template #label="{lang}">
                            <translate :key="lang">Message Body on Station Offline</translate>
                        </template>
                    </b-wrapped-form-group>
                </template>
                <template v-if="hasTrigger('station_online')">
                    <b-wrapped-form-group class="col-md-12" id="form_config_message_station_online"
                                          :field="form.config.message_station_online" input-type="textarea">
                        <template #label="{lang}">
                            <translate :key="lang">Message Body on Station Online</translate>
                        </template>
                    </b-wrapped-form-group>
                </template>
            </b-form-row>
        </b-form-group>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import CommonFormattingInfo from "./FormattingInfo";
import _ from 'lodash';

export default {
    name: 'CommonSocialPostFields',
    components: {CommonFormattingInfo, BWrappedFormGroup},
    props: {
        form: Object,
        nowPlayingUrl: String
    },
    methods: {
        hasTrigger(trigger) {
            return _.includes(this.form.triggers.$model, trigger);
        }
    }
}
</script>
