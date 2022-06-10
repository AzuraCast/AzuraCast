<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-form-row>
                <b-wrapped-form-group class="col-md-6" id="form_edit_name" :field="form.name">
                    <template #label="{lang}">
                        <translate :key="lang">Playlist Name</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox class="col-md-6" id="form_edit_is_enabled" :field="form.is_enabled">
                    <template #label="{lang}">
                        <translate :key="lang">Enable</translate>
                    </template>
                    <template #description="{lang}">
                        <translate :key="lang">If disabled, the playlist will not be included in radio playback, but can still be managed.</translate>
                    </template>
                </b-wrapped-form-checkbox>

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
                        <b-wrapped-form-checkbox class="col-md-6" id="form_edit_avoid_duplicates"
                                                 :field="form.avoid_duplicates">
                            <template #label="{lang}">
                                <translate :key="lang">Avoid Duplicate Artists/Titles</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">Whether the AutoDJ should attempt to avoid duplicate artists and track titles when playing media from this playlist.</translate>
                            </template>
                        </b-wrapped-form-checkbox>

                        <b-wrapped-form-checkbox class="col-md-6" id="form_edit_include_in_on_demand"
                                                 :field="form.include_in_on_demand">
                            <template #label="{lang}">
                                <translate :key="lang">Include in On-Demand Player</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">If this station has on-demand streaming and downloading enabled, only songs that are in playlists with this setting enabled will be visible.</translate>
                            </template>
                        </b-wrapped-form-checkbox>

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

                        <b-wrapped-form-group class="col-md-6" id="edit_form_type" :field="form.type">
                            <template #label="{lang}">
                                <translate :key="lang">Playlist Type</translate>
                            </template>
                            <template #default="props">
                                <b-form-radio-group stacked :id="props.id" v-model="props.field.$model">
                                    <b-form-radio value="default">
                                        <translate key="lang_form_type_default">General Rotation</translate>
                                        <translate class="form-text mt-0" key="lang_form_type_default_desc">Standard playlist, shuffles with other standard playlists based on weight.</translate>
                                    </b-form-radio>
                                    <b-form-radio value="once_per_x_songs">
                                        <translate key="lang_form_type_once_per_x_songs">Once per x Songs</translate>
                                        <translate class="form-text mt-0" key="lang_form_type_once_per_x_songs_desc">Play exactly once every $x songs.</translate>
                                    </b-form-radio>
                                    <b-form-radio value="once_per_x_minutes">
                                        <translate
                                            key="lang_form_type_once_per_x_minutes">Once per x Minutes</translate>
                                        <translate class="form-text mt-0" key="lang_form_type_once_per_x_minutes_desc">Play exactly once every $x minutes.</translate>
                                    </b-form-radio>
                                    <b-form-radio value="once_per_hour">
                                        <translate key="lang_form_type_once_per_hour">Once per Hour</translate>
                                        <translate class="form-text mt-0" key="lang_form_type_once_per_hour_desc">Play once per hour at the specified minute.</translate>
                                    </b-form-radio>
                                    <b-form-radio value="custom">
                                        <translate key="lang_form_type_custom">Advanced</translate>
                                        <span class="form-text mt-0">
                                <translate key="lang_form_type_custom_desc">Manually define how this playlist is used in Liquidsoap configuration.</translate>
                                <a href="https://docs.azuracast.com/en/user-guide/playlists/advanced-playlists"
                                   target="_blank">
                                    <translate
                                        key="lang_form_type_custom_more">Learn about Advanced Playlists</translate>
                                </a>
                            </span>
                                    </b-form-radio>
                                </b-form-radio-group>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-6" id="edit_form_order" :field="form.order">
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
                    </b-form-row>
                </b-form-group>

                <b-form-fieldset v-show="form.type.$model === 'default'">
                    <template #label>
                        <translate key="lang_type_default">General Rotation</translate>
                    </template>

                    <b-form-group>
                        <b-form-row>
                            <b-wrapped-form-group class="col-md-12" id="form_edit_weight" :field="form.weight">
                                <template #label="{lang}">
                                    <translate :key="lang">Playlist Weight</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">Higher weight playlists are played more frequently compared to other lower-weight playlists.</translate>
                                </template>
                                <template #default="props">
                                    <b-form-select :id="props.id" v-model="props.field.$model" :options="weightOptions"
                                                   :state="props.state"></b-form-select>
                                </template>
                            </b-wrapped-form-group>
                        </b-form-row>
                    </b-form-group>
                </b-form-fieldset>

                <b-form-fieldset v-show="form.type.$model === 'once_per_x_songs'">
                    <template #label>
                        <translate key="lang_type_once_per_x_songs">Once per x Songs</translate>
                    </template>

                    <b-form-group>
                        <b-form-row>
                            <b-wrapped-form-group class="col-md-12" id="form_edit_play_per_songs"
                                                  :field="form.play_per_songs" input-type="number"
                                                  :input-attrs="{min: '0', max: '150'}">
                                <template #label="{lang}">
                                    <translate :key="lang">Number of Songs Between Plays</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">This playlist will play every $x songs, where $x is specified here.</translate>
                                </template>
                            </b-wrapped-form-group>
                        </b-form-row>
                    </b-form-group>
                </b-form-fieldset>

                <b-form-fieldset v-show="form.type.$model === 'once_per_x_minutes'">
                    <template #label>
                        <translate key="lang_form_type_once_per_x_minutes">Once per x Minutes</translate>
                    </template>

                    <b-form-group>
                        <b-form-row>

                            <b-wrapped-form-group class="col-md-12" id="form_edit_play_per_minutes"
                                                  :field="form.play_per_minutes" input-type="number"
                                                  :input-attrs="{min: '0', max: '360'}">
                                <template #label="{lang}">
                                    <translate :key="lang">Number of Minutes Between Plays</translate>
                                </template>
                                <template #description="{lang}">
                                    <translate :key="lang">This playlist will play every $x minutes, where $x is specified here.</translate>
                                </template>
                            </b-wrapped-form-group>

                        </b-form-row>
                    </b-form-group>
                </b-form-fieldset>

                <b-form-fieldset v-show="form.type.$model === 'once_per_hour'">
                    <template #label>
                        <translate key="lang_type_once_per_hour">Once per Hour</translate>
                    </template>

                    <b-form-group>
                        <b-form-row>
                            <b-wrapped-form-group class="col-md-12" id="form_edit_play_per_hour_minute"
                                                  :field="form.play_per_hour_minute" input-type="number"
                                                  :input-attrs="{min: '0', max: '59'}">
                                <template #label="{lang}">
                                    <translate :key="lang">Minute of Hour to Play</translate>
                                </template>
                                <template #description="{lang}">
                                <translate
                                    :key="lang">Specify the minute of every hour that this playlist should play.</translate>
                                </template>
                            </b-wrapped-form-group>
                        </b-form-row>
                    </b-form-group>
                </b-form-fieldset>
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
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import BFormFieldset from "~/components/Form/BFormFieldset";

export default {
    name: 'PlaylistEditBasicInfo',
    components: {BFormFieldset, BWrappedFormCheckbox, BWrappedFormGroup},
    props: {
        form: Object
    },
    data() {
        let weightOptions = [
            {value: 1, text: '1 - ' + this.$gettext('Low')},
            {value: 2, text: '2'},
            {value: 3, text: '3 - ' + this.$gettext('Default')},
            {value: 4, text: '4'},
            {value: 5, text: '5 - ' + this.$gettext('High')}
        ];
        for (var i = 6; i <= 25; i++) {
            weightOptions.push({value: i, text: i});
        }

        return {
            weightOptions: weightOptions
        };
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Basic Info');
        }
    }
};
</script>
