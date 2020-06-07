<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="form_edit_name">
                    <template v-slot:label>
                        <translate>Playlist Name</translate>
                    </template>
                    <b-form-input id="form_edit_name" type="text" v-model="form.name.$model"
                                  :state="form.name.$dirty ? !form.name.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate>This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_weight">
                    <template v-slot:label>
                        <translate>Playlist Weight</translate>
                    </template>
                    <template v-slot:description>
                        <translate>Higher weight playlists are played more frequently compared to other lower-weight playlists.</translate>
                    </template>
                    <b-form-select id="form_edit_weight" v-model="form.weight.$model" :options="weightOptions"
                                   :state="form.weight.$dirty ? !form.weight.$error : null"></b-form-select>
                    <b-form-invalid-feedback>
                        <translate>This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_is_enabled">
                    <template v-slot:description>
                        <translate>If disabled, the playlist will not be included in radio playback, but can still be managed.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_is_enabled" v-model="form.is_enabled.$model">
                        <translate>Is Enabled</translate>
                    </b-form-checkbox>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="form_edit_avoid_duplicates">
                    <template v-slot:description>
                        <translate>Whether the AutoDJ should attempt to avoid duplicate artists and track titles when playing media from this playlist.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_avoid_duplicates" v-model="form.avoid_duplicates.$model">
                        <translate>Avoid Duplicate Artists/Titles</translate>
                    </b-form-checkbox>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="form_edit_include_in_on_demand">
                    <template v-slot:description>
                        <translate>If this station has on-demand streaming and downloading enabled, only songs that are in playlists with this setting enabled will be visible.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_include_in_on_demand" v-model="form.include_in_on_demand.$model">
                        <translate>Include in On-Demand Player</translate>
                    </b-form-checkbox>
                </b-form-group>

                <b-form-group class="col-md-12" label-for="edit_form_type">
                    <template v-slot:label>
                        <translate>Playlist Type</translate>
                    </template>
                    <b-form-radio-group stacked id="edit_form_type" v-model="form.type.$model">
                        <b-form-radio value="default">
                            <b>
                                <translate>General Rotation</translate>
                                :</b>
                            <translate>Standard playlist, shuffles with other standard playlists based on weight.</translate>
                        </b-form-radio>
                        <b-form-radio value="once_per_x_songs">
                            <b>
                                <translate>Once per x Songs</translate>
                                :
                            </b>
                            <translate>Play exactly once every <i>x</i> songs.</translate>
                        </b-form-radio>
                        <b-form-radio value="once_per_x_minutes">
                            <b>
                                <translate>Once per x Minutes</translate>
                                :
                            </b>
                            <translate>Play exactly once every <i>x</i> minutes.</translate>
                        </b-form-radio>
                        <b-form-radio value="once_per_hour">
                            <b>
                                <translate>Once per Hour</translate>
                                :
                            </b>
                            <translate>Play once per hour at the specified minute.</translate>
                        </b-form-radio>
                        <b-form-radio value="custom">
                            <b>
                                <translate>Advanced</translate>
                                :
                            </b>
                            <translate>Manually define how this playlist is used in Liquidsoap configuration.</translate>
                            <a href="https://www.azuracast.com/station-management/playlists/#advanced-playlists" target="_blank">
                                <translate>Learn about Advanced Playlists</translate>
                            </a>
                        </b-form-radio>
                    </b-form-radio-group>
                </b-form-group>
            </b-row>
        </b-form-group>

        <b-form-group v-show="form.type.$model === 'default'">
            <template v-slot:label>
                <translate>General Rotation</translate>
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_include_in_automation">
                    <template v-slot:description>
                        <translate>If auto-assignment is enabled, use this playlist as one of the targets for songs to be redistributed into. This will overwrite the existing contents of this playlist.</translate>
                    </template>
                    <b-form-checkbox id="form_edit_include_in_automation" v-model="form.include_in_automation.$model">
                        <translate>Include in Automated Assignment</translate>
                    </b-form-checkbox>
                </b-form-group>

            </b-row>
        </b-form-group>

        <b-form-group v-show="form.type.$model === 'once_per_x_songs'">
            <template v-slot:label>
                <translate>Once per x Songs</translate>
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_play_per_songs">
                    <template v-slot:label>
                        <translate>Number of Songs Between Plays</translate>
                    </template>
                    <template v-slot:description>
                        <translate>This playlist will play every $x songs, where $x is specified below.</translate>
                    </template>
                    <b-form-input id="form_edit_play_per_songs" type="number" min="0" max="150"
                                  v-model="form.play_per_songs.$model"
                                  :state="form.play_per_songs.$dirty ? !form.play_per_songs.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate>This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

            </b-row>
        </b-form-group>

        <b-form-group v-show="form.type.$model === 'once_per_x_minutes'">
            <template v-slot:label>
                <translate>Once per x Minutes</translate>
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_play_per_minutes">
                    <template v-slot:label>
                        <translate>Number of Minutes Between Plays</translate>
                    </template>
                    <template v-slot:description>
                        <translate>This playlist will play every $x minutes, where $x is specified below.</translate>
                    </template>
                    <b-form-input id="form_edit_play_per_minutes" type="number" min="0" max="360"
                                  v-model="form.play_per_minutes.$model"
                                  :state="form.play_per_minutes.$dirty ? !form.play_per_minutes.$error : null"></b-form-input>
                    <b-form-invalid-feedback>
                        <translate>This field is required.</translate>
                    </b-form-invalid-feedback>
                </b-form-group>

            </b-row>
        </b-form-group>

        <b-form-group v-show="form.type.$model === 'once_per_hour'">
            <template v-slot:label>
                <translate>Once per Hour</translate>
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_play_per_hour_minute">
                    <template v-slot:label>
                        <translate>Minute of Hour to Play</translate>
                    </template>
                    <template v-slot:description>
                        <translate>Specify the minute of every hour that this playlist should play.</translate>
                    </template>
                    <b-form-input id="form_edit_play_per_hour_minute" type="number" min="0" max="59"
                                  v-model="form.play_per_hour_minute.$model"
                                  :state="form.play_per_hour_minute.$dirty ? !form.play_per_hour_minute.$error : null"></b-form-input>
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
        name: 'PlaylistEditBasicInfo',
        props: {
            form: Object
        },
        data () {
            let weightOptions = [
                { value: 1, text: '1 - ' + this.$gettext('Low') },
                { value: 2, text: '2' },
                { value: 3, text: '3 - ' + this.$gettext('Default') },
                { value: 4, text: '4' },
                { value: 5, text: '5 - ' + this.$gettext('High') }
            ];
            for (var i = 6; i <= 25; i++) {
                weightOptions.push({ value: i, text: i });
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
