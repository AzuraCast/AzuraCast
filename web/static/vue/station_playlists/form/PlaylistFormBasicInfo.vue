<template>
    <b-tab :title="langTabTitle" active>
        <b-form-group>
            <b-row>
                <b-form-group class="col-md-6" label-for="form_edit_name">
                    <template v-slot:label v-translate>
                        Playlist Name
                    </template>
                    <b-form-input id="form_edit_name" type="text" v-model="form.name.$model"
                                  :state="form.name.$dirty ? !form.name.$error : null"></b-form-input>
                    <b-form-invalid-feedback v-translate>
                        This field is required.
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="form_edit_weight">
                    <template v-slot:label v-translate>
                        Playlist Weight
                    </template>
                    <template v-slot:description v-translate>
                        Higher weight playlists are played more frequently compared to other lower-weight
                        playlists.
                    </template>
                    <b-form-select id="form_edit_weight" v-model="form.weight.$model" :options="weightOptions"
                                   :state="form.weight.$dirty ? !form.weight.$error : null"></b-form-select>
                    <b-form-invalid-feedback v-translate>
                        This field is required.
                    </b-form-invalid-feedback>
                </b-form-group>
                <b-form-group class="col-md-12" label-for="form_edit_is_enabled">
                    <template v-slot:description v-translate>
                        If disabled, the playlist will not be included in radio playback, but can still be
                        managed.
                    </template>
                    <b-form-checkbox id="form_edit_is_enabled" v-model="form.is_enabled.$model">
                        <translate>Is Enabled</translate>
                    </b-form-checkbox>
                </b-form-group>
                <b-form-group class="col-md-6" label-for="edit_form_type">
                    <template v-slot:label v-translate>
                        Playlist Type
                    </template>
                    <b-form-radio-group stacked id="edit_form_type" v-model="form.type.$model">
                        <b-form-radio value="default">
                            <b>
                                <translate>General Rotation</translate>
                                :</b>
                            <translate>
                                Standard playlist, shuffles with other standard playlists based on weight.
                            </translate>
                        </b-form-radio>
                        <b-form-radio value="once_per_x_songs">
                            <b>
                                <translate>Once per x Songs</translate>
                                :
                            </b>
                            <translate>
                                Play exactly once every <i>x</i> songs.
                            </translate>
                        </b-form-radio>
                        <b-form-radio value="once_per_x_minutes">
                            <b>
                                <translate>Once per x Minutes</translate>
                                :
                            </b>
                            <translate>
                                Play exactly once every <i>x</i> minutes.
                            </translate>
                        </b-form-radio>
                        <b-form-radio value="once_per_hour">
                            <b>
                                <translate>Once per Hour</translate>
                                :
                            </b>
                            <translate>
                                Play once per hour at the specified minute.
                            </translate>
                        </b-form-radio>
                        <b-form-radio value="advanced">
                            <b>
                                <translate>Advanced</translate>
                                :
                            </b>
                            <translate>
                                Manually define how this playlist is used in Liquidsoap configuration.
                            </translate>
                            <a href="https://www.azuracast.com/help/advanced_playlists.html" target="_blank"
                               v-translate>
                                Learn about Advanced Playlists
                            </a>
                        </b-form-radio>
                    </b-form-radio-group>
                </b-form-group>

                <b-form-group class="col-md-6" label-for="edit_form_order">
                    <template v-slot:label v-translate>
                        AutoDJ Scheduling Options
                    </template>
                    <template v-slot:description>
                        <translate>
                            Control how this playlist is handled by the AutoDJ software.
                        </translate>
                        <br>
                        <b>
                            <translate>Warning</translate>
                            :</b>
                        <translate>
                            These functions are internal to Liquidsoap and will affect how your AutoDJ works.
                        </translate>
                    </template>

                    <b-form-checkbox-group stacked id="edit_form_backend_options" v-model="form.backend_options.$model">
                        <b-form-checkbox value="interrupt">
                            <translate>Interrupt other songs to play at scheduled time.</translate>
                        </b-form-checkbox>
                        <b-form-checkbox value="loop_once">
                            <translate>Only loop through playlist once.</translate>
                        </b-form-checkbox>
                        <b-form-checkbox value="single_track">
                            <translate>Only play one track at scheduled time.</translate>
                        </b-form-checkbox>
                        <b-form-checkbox value="merge">
                            <translate>Merge playlist to play as a single track.</translate>
                        </b-form-checkbox>
                    </b-form-checkbox-group>
                </b-form-group>
            </b-row>
        </b-form-group>

        <b-form-group v-if="form.type.$model === 'default'">
            <template v-slot:label v-translate>
                General Rotation
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_include_in_automation">
                    <template v-slot:description v-translate>
                        If auto-assignment is enabled, use this playlist as one of the targets for songs to be
                        redistributed into. This will overwrite the existing contents of this playlist.
                    </template>
                    <b-form-checkbox id="form_edit_include_in_automation" v-model="form.include_in_automation.$model">
                        <translate>Include in Automated Assignment</translate>
                    </b-form-checkbox>
                </b-form-group>

            </b-row>
        </b-form-group>

        <b-form-group v-if="form.type.$model === 'once_per_x_songs'">
            <template v-slot:label v-translate>
                Once per x Songs
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_play_per_songs">
                    <template v-slot:label v-translate>
                        Number of Songs Between Plays
                    </template>
                    <template v-slot:description v-translate>
                        This playlist will play every $x songs, where $x is specified below.
                    </template>
                    <b-form-input id="form_edit_play_per_songs" type="number" min="0" max="150"
                                  v-model="form.play_per_songs.$model"
                                  :state="form.play_per_songs.$dirty ? !form.play_per_songs.$error : null"></b-form-input>
                    <b-form-invalid-feedback v-translate>
                        This field is required.
                    </b-form-invalid-feedback>
                </b-form-group>

            </b-row>
        </b-form-group>

        <b-form-group v-if="form.type.$model === 'once_per_x_minutes'">
            <template v-slot:label v-translate>
                Once per x Minutes
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_play_per_minutes">
                    <template v-slot:label v-translate>
                        Number of Minutes Between Plays
                    </template>
                    <template v-slot:description v-translate>
                        This playlist will play every $x minutes, where $x is specified below.
                    </template>
                    <b-form-input id="form_edit_play_per_minutes" type="number" min="0" max="360"
                                  v-model="form.play_per_minutes.$model"
                                  :state="form.play_per_minutes.$dirty ? !form.play_per_minutes.$error : null"></b-form-input>
                    <b-form-invalid-feedback v-translate>
                        This field is required.
                    </b-form-invalid-feedback>
                </b-form-group>

            </b-row>
        </b-form-group>

        <b-form-group v-if="form.type.$model === 'once_per_hour'">
            <template v-slot:label v-translate>
                Once per Hour
            </template>
            <b-row>

                <b-form-group class="col-md-6" label-for="form_edit_play_per_hour_minute">
                    <template v-slot:label v-translate>
                        Minute of Hour to Play
                    </template>
                    <template v-slot:description v-translate>
                        Specify the minute of every hour that this playlist should play.
                    </template>
                    <b-form-input id="form_edit_play_per_hour_minute" type="number" min="0" max="59"
                                  v-model="form.play_per_hour_minute.$model"
                                  :state="form.play_per_hour_minute.$dirty ? !form.play_per_hour_minute.$error : null"></b-form-input>
                    <b-form-invalid-feedback v-translate>
                        This field is required.
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
            ]
            for (var i = 6; i <= 25; i++) {
                weightOptions.push({ value: i, text: i })
            }

            return {
                weightOptions: weightOptions
            }
        },
        computed: {
            langTabTitle () {
                return this.$gettext('Basic Info')
            }
        }
    }
</script>