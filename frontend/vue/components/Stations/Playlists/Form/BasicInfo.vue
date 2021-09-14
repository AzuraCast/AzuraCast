<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-6" id="form_edit_name" :field="form.name">
                    <template #label>
                        <translate key="lang_form_edit_name">Playlist Name</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_weight" :field="form.weight">
                    <template #label>
                        <translate key="lang_form_edit_weight">Playlist Weight</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_edit_weight_desc">Higher weight playlists are played more frequently compared to other lower-weight playlists.</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model" :options="weightOptions"
                                       :state="props.state"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_is_enabled" :field="form.is_enabled">
                    <template #description>
                        <translate key="lang_form_edit_is_enabled_desc">If disabled, the playlist will not be included in radio playback, but can still be managed.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_form_edit_is_enabled">Is Enabled</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="form_edit_avoid_duplicates" :field="form.avoid_duplicates">
                    <template #description>
                        <translate key="lang_form_edit_avoid_duplicates_desc">Whether the AutoDJ should attempt to avoid duplicate artists and track titles when playing media from this playlist.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_form_edit_avoid_duplicates">Avoid Duplicate Artists/Titles</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="form_edit_include_in_on_demand"
                                      :field="form.include_in_on_demand">
                    <template #description>
                        <translate key="lang_form_edit_include_in_on_demand_desc">If this station has on-demand streaming and downloading enabled, only songs that are in playlists with this setting enabled will be visible.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate key="lang_form_edit_include_in_on_demand">Include in On-Demand Player</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_type" :field="form.type">
                    <template #label>
                        <translate key="lang_edit_form_type">Playlist Type</translate>
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
                                <translate key="lang_form_type_once_per_x_minutes">Once per x Minutes</translate>
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
            </b-row>
        </b-form-group>

        <b-card v-show="form.type.$model === 'default'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_type_default">General Rotation</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>
                        <b-wrapped-form-group class="col-md-6" id="form_edit_include_in_automation"
                                              :field="form.include_in_automation">
                            <template #description>
                                <translate key="lang_form_edit_include_in_automation_desc">If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.</translate>
                            </template>
                            <template #default="props">
                                <b-form-checkbox :id="props.id" v-model="props.field.$model">
                                    <translate key="lang_form_edit_include_in_automation">Include in Automated Assignment</translate>
                                </b-form-checkbox>
                            </template>
                        </b-wrapped-form-group>
                    </b-row>
                </b-form-group>
            </b-card-body>
        </b-card>

        <b-card v-show="form.type.$model === 'once_per_x_songs'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_type_once_per_x_songs">Once per x Songs</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>
                        <b-wrapped-form-group class="col-md-6" id="form_edit_play_per_songs"
                                              :field="form.play_per_songs">
                            <template #label>
                                <translate key="lang_form_edit_play_per_songs">Number of Songs Between Plays</translate>
                            </template>
                            <template #description>
                                <translate key="lang_form_edit_play_per_songs_desc">This playlist will play every $x songs, where $x is specified below.</translate>
                            </template>
                            <template #default="props">
                                <b-form-input :id="props.id" type="number" min="0" max="150"
                                              v-model="props.field.$model" :state="props.state"></b-form-input>
                            </template>
                        </b-wrapped-form-group>
                    </b-row>
                </b-form-group>
            </b-card-body>
        </b-card>

        <b-card v-show="form.type.$model === 'once_per_x_minutes'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_form_type_once_per_x_minutes">Once per x Minutes</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_play_per_minutes"
                                              :field="form.play_per_minutes">
                            <template #label>
                                <translate key="form_edit_play_per_minutes">Number of Minutes Between Plays</translate>
                            </template>
                            <template #description>
                                <translate key="form_edit_play_per_minutes_desc">This playlist will play every $x minutes, where $x is specified below.</translate>
                            </template>
                            <template #default="props">
                                <b-form-input :id="props.id" type="number" min="0" max="360"
                                              v-model="props.field.$model" :state="props.state"></b-form-input>
                            </template>
                        </b-wrapped-form-group>

                    </b-row>
                </b-form-group>
            </b-card-body>
        </b-card>

        <b-card v-show="form.type.$model === 'once_per_hour'" class="mb-3" no-body>
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    <translate key="lang_type_once_per_hour">Once per Hour</translate>
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <b-row>

                        <b-wrapped-form-group class="col-md-6" id="form_edit_play_per_hour_minute"
                                              :field="form.play_per_hour_minute">
                            <template #label>
                                <translate key="lang_form_edit_play_per_hour_minute">Minute of Hour to Play</translate>
                            </template>
                            <template #description>
                                <translate key="lang_form_edit_play_per_hour_minute_desc">Specify the minute of every hour that this playlist should play.</translate>
                            </template>
                            <template #default="props">
                                <b-form-input :id="props.id" type="number" min="0" max="59"
                                              v-model="props.field.$model" :state="props.state"></b-form-input>
                            </template>
                        </b-wrapped-form-group>

                    </b-row>
                </b-form-group>
            </b-card-body>
        </b-card>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'PlaylistEditBasicInfo',
    components: {BWrappedFormGroup},
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
        langTabTitle () {
            return this.$gettext('Basic Info');
        }
    }
};
</script>
