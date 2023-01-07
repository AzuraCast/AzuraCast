<template>
    <b-tab
        :title="$gettext('Basic Info')"
        active
    >
        <b-form-group>
            <div class="form-row">
                <b-wrapped-form-group
                    id="form_edit_name"
                    class="col-md-6"
                    :field="form.name"
                >
                    <template #label>
                        {{ $gettext('Playlist Name') }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox
                    id="form_edit_is_enabled"
                    class="col-md-6"
                    :field="form.is_enabled"
                >
                    <template #label>
                        {{ $gettext('Enable') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('If disabled, the playlist will not be included in radio playback, but can still be managed.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group
                    id="edit_form_source"
                    class="col-md-12"
                    :field="form.source"
                >
                    <template #label>
                        {{ $gettext('Source') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                        >
                            <b-form-radio value="songs">
                                {{ $gettext('Song-Based') }}
                                {{ $gettext('A playlist containing media files hosted on this server.') }}
                            </b-form-radio>
                            <b-form-radio value="remote_url">
                                {{ $gettext('Remote URL') }}
                                {{ $gettext('A playlist that instructs the station to play from a remote URL.') }}
                            </b-form-radio>
                        </b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-group>

        <b-card
            v-show="form.source.$model === 'songs'"
            class="mb-3"
            no-body
        >
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Song-Based Playlist') }}
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <div class="form-row">
                        <b-wrapped-form-checkbox
                            id="form_edit_avoid_duplicates"
                            class="col-md-6"
                            :field="form.avoid_duplicates"
                        >
                            <template #label>
                                {{ $gettext('Avoid Duplicate Artists/Titles') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('Whether the AutoDJ should attempt to avoid duplicate artists and track titles when playing media from this playlist.')
                                }}
                            </template>
                        </b-wrapped-form-checkbox>

                        <b-wrapped-form-checkbox
                            id="form_edit_include_in_on_demand"
                            class="col-md-6"
                            :field="form.include_in_on_demand"
                        >
                            <template #label>
                                {{ $gettext('Include in On-Demand Player') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('If this station has on-demand streaming and downloading enabled, only songs that are in playlists with this setting enabled will be visible.')
                                }}
                            </template>
                        </b-wrapped-form-checkbox>

                        <b-wrapped-form-group
                            id="form_edit_include_in_requests"
                            class="col-md-6"
                            :field="form.include_in_requests"
                        >
                            <template #description>
                                {{
                                    $gettext('If requests are enabled for your station, users will be able to request media that is on this playlist.')
                                }}
                            </template>
                            <template #default="slotProps">
                                <b-form-checkbox
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                >
                                    {{ $gettext('Allow Requests from This Playlist') }}
                                </b-form-checkbox>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="form_edit_is_jingle"
                            class="col-md-6"
                            :field="form.is_jingle"
                        >
                            <template #description>
                                {{
                                    $gettext('Enable this setting to prevent metadata from being sent to the AutoDJ for files in this playlist. This is useful if the playlist contains jingles or bumpers.')
                                }}
                            </template>
                            <template #default="slotProps">
                                <b-form-checkbox
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                >
                                    {{ $gettext('Hide Metadata from Listeners ("Jingle Mode")') }}
                                </b-form-checkbox>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="edit_form_type"
                            class="col-md-6"
                            :field="form.type"
                        >
                            <template #label>
                                {{ $gettext('Playlist Type') }}
                            </template>
                            <template #default="slotProps">
                                <b-form-radio-group
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    stacked
                                >
                                    <b-form-radio value="default">
                                        {{ $gettext('General Rotation') }}
                                        {{
                                            $gettext('Standard playlist, shuffles with other standard playlists based on weight.')
                                        }}
                                    </b-form-radio>
                                    <b-form-radio value="once_per_x_songs">
                                        {{ $gettext('Once per x Songs') }}
                                        {{ $gettext('Play exactly once every $x songs.') }}
                                    </b-form-radio>
                                    <b-form-radio value="once_per_x_minutes">
                                        {{ $gettext('Once per x Minutes') }}
                                        {{ $gettext('Play exactly once every $x minutes.') }}
                                    </b-form-radio>
                                    <b-form-radio value="once_per_hour">
                                        {{ $gettext('Once per Hour') }}
                                        {{ $gettext('Play once per hour at the specified minute.') }}
                                    </b-form-radio>
                                    <b-form-radio value="custom">
                                        {{ $gettext('Advanced') }}
                                        <span class="form-text mt-0">
                                            {{ $gettext('Manually define how this playlist is used in Liquidsoap configuration.') }}
                                            <a
                                                href="https://docs.azuracast.com/en/user-guide/playlists/advanced-playlists"
                                                target="_blank"
                                            >
                                                {{ $gettext('Learn about Advanced Playlists') }}
                                            </a>
                                        </span>
                                    </b-form-radio>
                                </b-form-radio-group>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="edit_form_order"
                            class="col-md-6"
                            :field="form.order"
                        >
                            <template #label>
                                {{ $gettext('Song Playback Order') }}
                            </template>
                            <template #default="slotProps">
                                <b-form-radio-group
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    stacked
                                >
                                    <b-form-radio value="shuffle">
                                        {{ $gettext('Shuffled') }}
                                        {{
                                            $gettext('The full playlist is shuffled and then played through in the shuffled order.')
                                        }}
                                    </b-form-radio>
                                    <b-form-radio value="random">
                                        {{ $gettext('Random') }}
                                        {{
                                            $gettext('A completely random track is picked for playback every time the queue is populated.')
                                        }}
                                    </b-form-radio>
                                    <b-form-radio value="sequential">
                                        {{ $gettext('Sequential') }}
                                        {{
                                            $gettext('The order of the playlist is manually specified and followed by the AutoDJ.')
                                        }}
                                    </b-form-radio>
                                </b-form-radio-group>
                            </template>
                        </b-wrapped-form-group>
                    </div>
                </b-form-group>

                <b-form-fieldset v-show="form.type.$model === 'default'">
                    <template #label>
                        {{ $gettext('General Rotation') }}
                    </template>

                    <b-form-group>
                        <div class="form-row">
                            <b-wrapped-form-group
                                id="form_edit_weight"
                                class="col-md-12"
                                :field="form.weight"
                            >
                                <template #label>
                                    {{ $gettext('Playlist Weight') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('Higher weight playlists are played more frequently compared to other lower-weight playlists.')
                                    }}
                                </template>
                                <template #default="slotProps">
                                    <b-form-select
                                        :id="slotProps.id"
                                        v-model="slotProps.field.$model"
                                        :options="weightOptions"
                                        :state="slotProps.state"
                                    />
                                </template>
                            </b-wrapped-form-group>
                        </div>
                    </b-form-group>
                </b-form-fieldset>

                <b-form-fieldset v-show="form.type.$model === 'once_per_x_songs'">
                    <template #label>
                        {{ $gettext('Once per x Songs') }}
                    </template>

                    <b-form-group>
                        <div class="form-row">
                            <b-wrapped-form-group
                                id="form_edit_play_per_songs"
                                class="col-md-12"
                                :field="form.play_per_songs"
                                input-type="number"
                                :input-attrs="{min: '0', max: '150'}"
                            >
                                <template #label>
                                    {{ $gettext('Number of Songs Between Plays') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This playlist will play every $x songs, where $x is specified here.')
                                    }}
                                </template>
                            </b-wrapped-form-group>
                        </div>
                    </b-form-group>
                </b-form-fieldset>

                <b-form-fieldset v-show="form.type.$model === 'once_per_x_minutes'">
                    <template #label>
                        {{ $gettext('Once per x Minutes') }}
                    </template>

                    <b-form-group>
                        <div class="form-row">
                            <b-wrapped-form-group
                                id="form_edit_play_per_minutes"
                                class="col-md-12"
                                :field="form.play_per_minutes"
                                input-type="number"
                                :input-attrs="{min: '0', max: '360'}"
                            >
                                <template #label>
                                    {{ $gettext('Number of Minutes Between Plays') }}
                                </template>
                                <template #description>
                                    {{
                                        $gettext('This playlist will play every $x minutes, where $x is specified here.')
                                    }}
                                </template>
                            </b-wrapped-form-group>
                        </div>
                    </b-form-group>
                </b-form-fieldset>

                <b-form-fieldset v-show="form.type.$model === 'once_per_hour'">
                    <template #label>
                        {{ $gettext('Once per Hour') }}
                    </template>

                    <b-form-group>
                        <div class="form-row">
                            <b-wrapped-form-group
                                id="form_edit_play_per_hour_minute"
                                class="col-md-12"
                                :field="form.play_per_hour_minute"
                                input-type="number"
                                :input-attrs="{min: '0', max: '59'}"
                            >
                                <template #label>
                                    {{ $gettext('Minute of Hour to Play') }}
                                </template>
                                <template #description>
                                    {{ $gettext('Specify the minute of every hour that this playlist should play.') }}
                                </template>
                            </b-wrapped-form-group>
                        </div>
                    </b-form-group>
                </b-form-fieldset>
            </b-card-body>
        </b-card>

        <b-card
            v-show="form.source.$model === 'remote_url'"
            class="mb-3"
            no-body
        >
            <div class="card-header bg-primary-dark">
                <h2 class="card-title">
                    {{ $gettext('Remote URL Playlist') }}
                </h2>
            </div>
            <b-card-body>
                <b-form-group>
                    <div class="form-row">
                        <b-wrapped-form-group
                            id="form_edit_remote_url"
                            class="col-md-6"
                            :field="form.remote_url"
                        >
                            <template #label>
                                {{ $gettext('Remote URL') }}
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="edit_form_remote_type"
                            class="col-md-6"
                            :field="form.remote_type"
                        >
                            <template #label>
                                {{ $gettext('Remote URL Type') }}
                            </template>
                            <template #default="slotProps">
                                <b-form-radio-group
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    stacked
                                >
                                    <b-form-radio value="stream">
                                        {{ $gettext('Direct Stream URL') }}
                                    </b-form-radio>
                                    <b-form-radio value="playlist">
                                        {{ $gettext('Playlist (M3U/PLS) URL') }}
                                    </b-form-radio>
                                </b-form-radio-group>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="form_edit_remote_buffer"
                            class="col-md-6"
                            :field="form.remote_buffer"
                        >
                            <template #label>
                                {{ $gettext('Remote Playback Buffer (Seconds)') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('The length of playback time that Liquidsoap should buffer when playing this remote playlist. Shorter times may lead to intermittent playback on unstable connections.')
                                }}
                            </template>
                            <template #default>
                                <b-form-input
                                    id="form_edit_remote_buffer"
                                    v-model="form.remote_buffer.$model"
                                    type="number"
                                    min="0"
                                    max="120"
                                    :state="form.remote_buffer.$dirty ? !form.remote_buffer.$error : null"
                                />
                            </template>
                        </b-wrapped-form-group>
                    </div>
                </b-form-group>
            </b-card-body>
        </b-card>
    </b-tab>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import BFormFieldset from "~/components/Form/BFormFieldset";
import {map, range} from "lodash";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const weightOptions = map(
    range(1, 26),
    (val) => {
        return {
            value: val,
            text: val
        }
    }
);
</script>
