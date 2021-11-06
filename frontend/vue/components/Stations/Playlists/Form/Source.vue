<template>
    <b-tab :title="langTabTitle">
        <b-form-group>
            <b-form-row>

                <b-wrapped-form-group class="col-md-12" id="edit_form_source" :field="form.source">
                    <template #label="{lang}">
                        <translate :key="lang">Source</translate>
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model">
                            <b-form-radio value="songs">
                                <translate key="lang_edit_form_source_songs">Song-Based</translate>
                                <translate class="form-text mt-0" key="lang_edit_form_source_songs_desc">A playlist containing media files hosted on this server.</translate>
                            </b-form-radio>
                            <b-form-radio value="remote_url">
                                <translate key="lang_edit_form_source_remote_url">Remote URL</translate>
                                <translate class="form-text mt-0" key="lang_edit_form_source_remote_url_desc">A playlist that instructs the station to play from a remote URL.</translate>
                            </b-form-radio>
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </b-form-row>
        </b-form-group>

        <b-card v-show="form.source.$model === 'songs'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_source_songs">Song-Based Playlist</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-form-row>

                        <b-wrapped-form-group class="col-md-12" id="edit_form_order" :field="form.order">
                            <template #label="{lang}">
                                <translate :key="lang">Song Playback Order</translate>
                            </template>
                            <template #default="props">
                                <b-form-radio-group stacked :id="props.id" v-model="props.field.$model">
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
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_include_in_requests"
                                              :field="form.include_in_requests">
                            <template #description="{lang}">
                                <translate :key="lang">If requests are enabled for your station, users will be able to request media that is on this playlist.</translate>
                            </template>
                            <template #default="props">
                                <b-form-checkbox :id="props.id" v-model="props.field.$model">
                                    <translate key="lang_form_edit_include_in_requests">Allow Requests from This Playlist</translate>
                                </b-form-checkbox>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_is_jingle" :field="form.is_jingle">
                            <template #description="{lang}">
                                <translate :key="lang">Enable this setting to prevent metadata from being sent to the AutoDJ for files in this playlist. This is useful if the playlist contains jingles or bumpers.</translate>
                            </template>
                            <template #default="props">
                                <b-form-checkbox :id="props.id" v-model="props.field.$model">
                                    <translate key="lang_form_edit_is_jingle">Hide Metadata from Listeners ("Jingle Mode")</translate>
                                </b-form-checkbox>
                            </template>
                        </b-wrapped-form-group>

                    </b-form-row>
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
                    <b-form-row>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_remote_url" :field="form.remote_url">
                            <template #label="{lang}">
                                <translate :key="lang">Remote URL</translate>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="edit_form_remote_type" :field="form.remote_type">
                            <template #label="{lang}">
                                <translate :key="lang">Remote URL Type</translate>
                            </template>
                            <template #default="props">
                                <b-form-radio-group stacked :id="props.id" v-model="props.field.$model">
                                    <b-form-radio value="stream">
                                        <translate key="lang_edit_form_remote_type_stream">Direct Stream URL</translate>
                                    </b-form-radio>
                                    <b-form-radio value="playlist">
                                        <translate
                                            key="lang_edit_form_remote_type_playlist">Playlist (M3U/PLS) URL</translate>
                                    </b-form-radio>
                                </b-form-radio-group>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_remote_buffer" :field="form.remote_buffer">
                            <template #label="{lang}">
                                <translate :key="lang">Remote Playback Buffer (Seconds)</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">The length of playback time that Liquidsoap should buffer when playing this remote playlist. Shorter times may lead to intermittent playback on unstable connections.</translate>
                            </template>
                            <template #default="props">
                                <b-form-input id="form_edit_remote_buffer" type="number" min="0" max="120"
                                              v-model="form.remote_buffer.$model"
                                              :state="form.remote_buffer.$dirty ? !form.remote_buffer.$error : null"></b-form-input>
                            </template>
                        </b-wrapped-form-group>

                    </b-form-row>
                </b-form-group>
            </b-card-body>
        </b-card>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'PlaylistEditSource',
    components: {BWrappedFormGroup},
    props: {
        form: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Source');
        }
    }
};
</script>
