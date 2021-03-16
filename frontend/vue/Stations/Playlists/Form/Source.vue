<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-12" label-for="edit_form_source">
                    <template v-slot:label>
                        <translate key="lang_edit_form_source">Source</translate>
                    </template>
                    <b-form-radio-group stacked id="edit_form_source" v-model="form.source.$model">
                        <b-form-radio value="songs">
                            <translate key="lang_edit_form_source_songs">Song-Based Playlist</translate>
                            <translate class="form-text mt-0" key="lang_edit_form_source_songs_desc">A playlist containing media files hosted on this server.</translate>
                        </b-form-radio>
                        <b-form-radio value="remote_url">
                            <translate key="lang_edit_form_source_remote_url">Remote URL Playlist</translate>
                            <translate class="form-text mt-0" key="lang_edit_form_source_remote_url_desc">A playlist that instructs the station to play from a remote URL.</translate>
                        </b-form-radio>
                    </b-form-radio-group>
                </b-form-group>
            </b-row>
        </b-form-group>

        <b-card v-show="form.source.$model === 'songs'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_source_songs">Song-Based Playlist</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>
                        <b-form-group class="col-md-12" label-for="edit_form_order">
                            <template v-slot:label>
                                <translate key="lang_edit_form_order">Song Playback Order</translate>
                            </template>
                            <b-form-radio-group stacked id="edit_form_order" v-model="form.order.$model">
                                <b-form-radio value="shuffle">
                                    <translate key="lang_edit_form_order_shuffle">Shuffled</translate>
                                    <translate class="form-text mt-0" key="lang_edit_form_order_shuffle_info">The full playlist is shuffled and then played through in the shuffled order.</translate>
                                </b-form-radio>
                                <b-form-radio value="random">
                                    <translate key="lang_edit_form_order_random">Random</translate>
                                    <translate class="form-text mt-0" key="lang_edit_form_order_random_info">A completely random track is picked for playback every time the queue is populated.</translate>
                                </b-form-radio>
                                <b-form-radio value="sequential">
                                    <translate key="lang_edit_form_order_sequential">Sequential</translate>
                                    <translate class="form-text mt-0" key="lang_edit_form_order_sequential_info">The order of the playlist is manually specified and followed by the AutoDJ.</translate>
                                </b-form-radio>
                            </b-form-radio-group>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_include_in_requests">
                            <template v-slot:description>
                                <translate key="lang_form_edit_include_in_requests_desc">If requests are enabled for your station, users will be able to request media that is on this playlist.</translate>
                            </template>
                            <b-form-checkbox id="form_edit_include_in_requests" v-model="form.include_in_requests.$model">
                                <translate key="lang_form_edit_include_in_requests">Allow Requests from This Playlist</translate>
                            </b-form-checkbox>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_is_jingle">
                            <template v-slot:description>
                                <translate key="lang_form_edit_is_jingle_desc">Enable this setting to prevent metadata from being sent to the AutoDJ for files in this playlist. This is useful if the playlist contains jingles or bumpers.</translate>
                            </template>
                            <b-form-checkbox id="form_edit_is_jingle" v-model="form.is_jingle.$model">
                                <translate key="lang_form_edit_is_jingle">Hide Metadata from Listeners ("Jingle Mode")</translate>
                            </b-form-checkbox>
                        </b-form-group>

                    </b-row>
                </b-form-group>
            </b-card-body>
        </b-card>

        <b-card v-show="form.source.$model === 'remote_url'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_source_remote_url">Remote URL Playlist</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>
                        <b-form-group class="col-md-6" label-for="form_edit_remote_url">
                            <template v-slot:label>
                                <translate key="lang_edit_form_remote_url">Remote URL</translate>
                            </template>
                            <b-form-input id="form_edit_remote_url" type="text"
                                          v-model="form.remote_url.$model"
                                          :state="form.remote_url.$dirty ? !form.remote_url.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="edit_form_remote_type">
                            <template v-slot:label>
                                <translate key="lang_edit_form_remote_type">Remote URL Type</translate>
                            </template>
                            <b-form-radio-group stacked id="edit_form_remote_type" v-model="form.remote_type.$model">
                                <b-form-radio value="stream">
                                    <translate key="lang_edit_form_remote_type_stream">Direct Stream URL</translate>
                                </b-form-radio>
                                <b-form-radio value="playlist">
                                    <translate key="lang_edit_form_remote_type_playlist">Playlist (M3U/PLS) URL</translate>
                                </b-form-radio>
                            </b-form-radio-group>
                        </b-form-group>

                        <b-form-group class="col-md-6" label-for="form_edit_remote_buffer">
                            <template v-slot:label>
                                <translate key="lang_form_edit_remote_buffer">Remote Playback Buffer (Seconds)</translate>
                            </template>
                            <template v-slot:description>
                                <translate key="lang_form_edit_remote_buffer_desc">The length of playback time that Liquidsoap should buffer when playing this remote playlist. Shorter times may lead to intermittent playback on unstable connections.</translate>
                            </template>
                            <b-form-input id="form_edit_remote_buffer" type="number" min="0" max="120"
                                          v-model="form.remote_buffer.$model"
                                          :state="form.remote_buffer.$dirty ? !form.remote_buffer.$error : null"></b-form-input>
                            <b-form-invalid-feedback>
                                <translate key="lang_error_required">This field is required.</translate>
                            </b-form-invalid-feedback>
                        </b-form-group>

                    </b-row>
                </b-form-group>
            </b-card-body>
        </b-card>
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