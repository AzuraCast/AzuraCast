<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="edit_form_source">
                    <template v-slot:label>
                        <translate>Source</translate>
                    </template>
                    <b-form-radio-group stacked id="edit_form_source" v-model="form.source.$model">
                        <b-form-radio value="songs">
                            <b>
                                <translate>Song-Based Playlist</translate>
                                :</b>
                            <translate>A playlist containing media files hosted on this server.</translate>
                        </b-form-radio>
                        <b-form-radio value="remote_url">
                            <b>
                                <translate>Remote URL Playlist</translate>
                                :</b>
                            <translate>A playlist that instructs the station to play from a remote URL.</translate>
                        </b-form-radio>
                    </b-form-radio-group>
                </b-form-group>
            </b-row>
        </b-form-group>

        <b-form-group v-if="form.source.$model === 'songs'">
            <template v-slot:label>
                <translate>Song-Based Playlist</translate>
            </template>

            <b-row>

                <b-form-group class="col-md-12" label-for="edit_form_order">
                    <template v-slot:label>
                        <translate>Song Playback Order</translate>
                    </template>
                    <b-form-radio-group stacked id="edit_form_order" v-model="form.order.$model">
                        <b-form-radio value="shuffle">
                            <template>Shuffled</template>
                        </b-form-radio>
                        <b-form-radio value="random">
                            <template>Random</template>
                        </b-form-radio>
                        <b-form-radio value="sequential">
                            <template>Sequential</template>
                        </b-form-radio>
                    </b-form-radio-group>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_include_in_requests">
                    <template v-slot:description>
                        <translate>If requests are enabled for your station, users will be able to request media that is on this playlist.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_include_in_requests" v-model="form.include_in_requests.$model">
                        <translate>Allow Requests from This Playlist</translate>
                    </b-form-checkbox>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_is_jingle">
                    <template v-slot:description>
                        <translate>Enable this setting to prevent metadata from being sent to the AutoDJ for files in this playlist. This is useful if the playlist contains jingles or bumpers.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_is_jingle" v-model="form.is_jingle.$model">
                        <translate>Hide Metadata from Listeners ("Jingle Mode")</translate>
                    </b-form-checkbox>
                </b-form-group>

            </b-row>
        </b-form-group>

        <b-form-group v-if="form.source.$model === 'remote_url'">
            <template v-slot:label>
                <translate>Remote URL Playlist</translate>
            </template>

            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_remote_url">
                    <template v-slot:label>
                        <translate>Remote URL</translate>
                    </template>
                    <b-form-input id="form_edit_remote_url" type="text"
                                  v-model="form.remote_url.$model"
                                  :state="form.remote_url.$dirty ? !form.remote_url.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate>This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_remote_type">
                    <template v-slot:label>
                        <translate>Remote URL Type</translate>
                    </template>
                    <b-form-radio-group stacked id="edit_form_remote_type" v-model="form.remote_type.$model">
                        <b-form-radio value="stream">
                            <template>Direct Stream URL</template>
                        </b-form-radio>
                        <b-form-radio value="playlist">
                            <template>Playlist (M3U/PLS) URL</template>
                        </b-form-radio>
                    </b-form-radio-group>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_remote_buffer">
                    <template v-slot:label>
                        <translate>Remote Playback Buffer (Seconds)</translate>
                    </template>
                    <template v-slot:description>
                        <translate>The length of playback time that Liquidsoap should buffer when playing this remote playlist. Shorter times may lead to intermittent playback on unstable connections.</translate>
                    </template>
                    <b-form-input id="form_edit_remote_buffer" type="number" min="0" max="120"
                                  v-model="form.remote_buffer.$model"
                                  :state="form.remote_buffer.$dirty ? !form.remote_buffer.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate>This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
    export default {
        name: 'PlaylistEditSource',
        props: {
            form: Object
        },
        computed: {
            langTabTitle () {
                return this.$gettext('Source');
            }
        }
    };
</script>