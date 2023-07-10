<template>
    <o-tab-item
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
        active
    >
        <div class="row g-3 mb-3">
            <form-group-field
                id="form_edit_name"
                class="col-md-6"
                :field="v$.name"
                :label="$gettext('Playlist Name')"
            />

            <form-group-checkbox
                id="form_edit_is_enabled"
                class="col-md-6"
                :field="v$.is_enabled"
                :label="$gettext('Enable')"
                :description="$gettext('If disabled, the playlist will not be included in radio playback, but can still be managed.')"
            />

            <form-group-multi-check
                id="edit_form_source"
                class="col-md-12"
                :field="v$.source"
                :options="sourceOptions"
                stacked
                radio
                :label="$gettext('Source')"
            >
                <template #label(songs)>
                    {{ $gettext('Song-Based') }}
                    <span class="form-text mt-0">
                        {{ $gettext('A playlist containing media files hosted on this server.') }}
                    </span>
                </template>
                <template #label(remote_url)>
                    {{ $gettext('Remote URL') }}
                    <span class="form-text mt-0">
                        {{ $gettext('A playlist that instructs the station to play from a remote URL.') }}
                    </span>
                </template>
            </form-group-multi-check>
        </div>

        <section
            v-show="form.source === 'songs'"
            class="card mb-3"
            role="region"
        >
            <div class="card-header text-bg-primary">
                <h2 class="card-title">
                    {{ $gettext('Song-Based Playlist') }}
                </h2>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <form-group-checkbox
                        id="form_edit_avoid_duplicates"
                        class="col-md-6"
                        :field="v$.avoid_duplicates"
                        :label="$gettext('Avoid Duplicate Artists/Titles')"
                        :description="$gettext('Whether the AutoDJ should attempt to avoid duplicate artists and track titles when playing media from this playlist.')"
                    />

                    <form-group-checkbox
                        id="form_edit_include_in_on_demand"
                        class="col-md-6"
                        :field="v$.include_in_on_demand"
                        :label="$gettext('Include in On-Demand Player')"
                        :description="$gettext('If this station has on-demand streaming and downloading enabled, only songs that are in playlists with this setting enabled will be visible.')"
                    />

                    <form-group-checkbox
                        id="form_edit_include_in_requests"
                        class="col-md-6"
                        :field="v$.include_in_requests"
                        :label="$gettext('Allow Requests from This Playlist')"
                        :description="$gettext('If requests are enabled for your station, users will be able to request media that is on this playlist.')"
                    />

                    <form-group-checkbox
                        id="form_edit_is_jingle"
                        class="col-md-6"
                        :field="v$.is_jingle"
                        :description="$gettext('Enable this setting to prevent metadata from being sent to the AutoDJ for files in this playlist. This is useful if the playlist contains jingles or bumpers.')"
                    >
                        <template #label>
                            {{ $gettext('Hide Metadata from Listeners ("Jingle Mode")') }}
                        </template>
                    </form-group-checkbox>

                    <form-group-multi-check
                        id="edit_form_type"
                        class="col-md-6"
                        :field="v$.type"
                        :options="typeOptions"
                        stacked
                        radio
                        :label="$gettext('Playlist Type')"
                    >
                        <template #label(default)>
                            {{ $gettext('General Rotation') }}
                            <span class="form-text mt-0">
                                {{
                                    $gettext('Standard playlist, shuffles with other standard playlists based on weight.')
                                }}
                            </span>
                        </template>
                        <template #label(once_per_x_songs)>
                            {{ $gettext('Once per x Songs') }}
                            <span class="form-text mt-0">
                                {{ $gettext('Play exactly once every $x songs.') }}
                            </span>
                        </template>
                        <template #label(once_per_x_minutes)>
                            {{ $gettext('Once per x Minutes') }}
                            <span class="form-text mt-0">
                                {{ $gettext('Play exactly once every $x minutes.') }}
                            </span>
                        </template>
                        <template #label(once_per_hour)>
                            {{ $gettext('Once per Hour') }}
                            <span class="form-text mt-0">
                                {{ $gettext('Play once per hour at the specified minute.') }}
                            </span>
                        </template>
                        <template #label(custom)>
                            {{ $gettext('Advanced') }}
                            <span class="form-text mt-0">
                                {{
                                    $gettext('Manually define how this playlist is used in Liquidsoap configuration.')
                                }}
                                <a
                                    href="https://docs.azuracast.com/en/user-guide/playlists/advanced-playlists"
                                    target="_blank"
                                >
                                    {{ $gettext('Learn about Advanced Playlists') }}
                                </a>
                            </span>
                        </template>
                    </form-group-multi-check>

                    <form-group-multi-check
                        id="edit_form_order"
                        class="col-md-6"
                        :field="v$.order"
                        :options="orderOptions"
                        stacked
                        radio
                        :label="$gettext('Song Playback Order')"
                    >
                        <template #label(shuffle)>
                            {{ $gettext('Shuffled') }}
                            <span class="form-text mt-0">
                                {{
                                    $gettext('The full playlist is shuffled and then played through in the shuffled order.')
                                }}
                            </span>
                        </template>
                        <template #label(random)>
                            {{ $gettext('Random') }}
                            <span class="form-text mt-0">
                                {{
                                    $gettext('A completely random track is picked for playback every time the queue is populated.')
                                }}
                            </span>
                        </template>
                        <template #label(sequential)>
                            {{ $gettext('Sequential') }}
                            <span class="form-text mt-0">
                                {{
                                    $gettext('The order of the playlist is manually specified and followed by the AutoDJ.')
                                }}
                            </span>
                        </template>
                    </form-group-multi-check>
                </div>

                <form-fieldset v-show="form.type === 'default'">
                    <template #label>
                        {{ $gettext('General Rotation') }}
                    </template>

                    <div class="row g-3">
                        <form-group-select
                            id="form_edit_weight"
                            class="col-md-12"
                            :field="v$.weight"
                            :options="weightOptions"
                            :label="$gettext('Playlist Weight')"
                            :description="$gettext('Higher weight playlists are played more frequently compared to other lower-weight playlists.')"
                        />
                    </div>
                </form-fieldset>

                <form-fieldset v-show="form.type === 'once_per_x_songs'">
                    <template #label>
                        {{ $gettext('Once per x Songs') }}
                    </template>

                    <div class="row g-3">
                        <form-group-field
                            id="form_edit_play_per_songs"
                            class="col-md-12"
                            :field="v$.play_per_songs"
                            input-type="number"
                            :input-attrs="{min: '0', max: '150'}"
                            :label="$gettext('Number of Songs Between Plays')"
                            :description="$gettext('This playlist will play every $x songs, where $x is specified here.')"
                        />
                    </div>
                </form-fieldset>

                <form-fieldset v-show="form.type === 'once_per_x_minutes'">
                    <template #label>
                        {{ $gettext('Once per x Minutes') }}
                    </template>

                    <div class="row g-3">
                        <form-group-field
                            id="form_edit_play_per_minutes"
                            class="col-md-12"
                            :field="v$.play_per_minutes"
                            input-type="number"
                            :input-attrs="{min: '0', max: '360'}"
                            :label="$gettext('Number of Minutes Between Plays')"
                            :description="$gettext('This playlist will play every $x minutes, where $x is specified here.')"
                        />
                    </div>
                </form-fieldset>

                <form-fieldset v-show="form.type === 'once_per_hour'">
                    <template #label>
                        {{ $gettext('Once per Hour') }}
                    </template>

                    <div class="row g-3">
                        <form-group-field
                            id="form_edit_play_per_hour_minute"
                            class="col-md-12"
                            :field="v$.play_per_hour_minute"
                            input-type="number"
                            :input-attrs="{min: '0', max: '59'}"
                            :label="$gettext('Minute of Hour to Play')"
                            :description="$gettext('Specify the minute of every hour that this playlist should play.')"
                        />
                    </div>
                </form-fieldset>
            </div>
        </section>

        <section
            v-show="form.source === 'remote_url'"
            class="card mb-3"
            role="region"
        >
            <div class="card-header text-bg-primary">
                <h2 class="card-title">
                    {{ $gettext('Remote URL Playlist') }}
                </h2>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <form-group-field
                        id="form_edit_remote_url"
                        class="col-md-6"
                        :field="v$.remote_url"
                        :label="$gettext('Remote URL')"
                    />

                    <form-group-multi-check
                        id="edit_form_remote_type"
                        class="col-md-6"
                        :field="v$.remote_type"
                        :options="remoteTypeOptions"
                        stacked
                        radio
                        :label="$gettext('Remote URL Type')"
                    />

                    <form-group-field
                        id="form_edit_remote_buffer"
                        class="col-md-6"
                        :field="v$.remote_buffer"
                        input-type="number"
                        :input-attrs="{ min: 0, max: 120 }"
                        :label="$gettext('Remote Playback Buffer (Seconds)')"
                        :description="$gettext('The length of playback time that Liquidsoap should buffer when playing this remote playlist. Shorter times may lead to intermittent playback on unstable connections.')"
                    />
                </div>
            </div>
        </section>
    </o-tab-item>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox";
import FormFieldset from "~/components/Form/FormFieldset";
import {map, range} from "lodash";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useTranslate} from "~/vendor/gettext";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$, tabClass} = useVuelidateOnFormTab(
    {
        name: {required},
        is_enabled: {},
        include_in_on_demand: {},
        weight: {},
        type: {},
        source: {},
        order: {},
        remote_url: {},
        remote_type: {},
        remote_buffer: {},
        is_jingle: {},
        play_per_songs: {},
        play_per_minutes: {},
        play_per_hour_minute: {},
        include_in_requests: {},
        avoid_duplicates: {}
    },
    form
);

// These don't need to be translated as they're overridden by slots above.
const sourceOptions = [
    {
        value: 'songs',
        text: 'Song-Based'
    },
    {
        value: 'remote_url',
        text: 'Remote URL'
    }
];

const typeOptions = [
    {
        value: 'default',
        text: 'General Rotation'
    },
    {
        value: 'once_per_x_songs',
        text: 'Once per X Songs'
    },
    {
        value: 'once_per_x_minutes',
        text: 'Once per X Minutes'
    },
    {
        value: 'once_per_hour',
        text: 'Once per Hour'
    },
    {
        value: 'custom',
        text: 'Advanced'
    }
];

const orderOptions = [
    {
        value: 'shuffle',
        text: 'Shuffled'
    },
    {
        value: 'random',
        text: 'Random'
    },
    {
        value: 'sequential',
        text: 'Sequential'
    }
];

const {$gettext} = useTranslate();

const remoteTypeOptions = [
    {
        value: 'stream',
        text: $gettext('Direct Stream URL')
    },
    {
        value: 'playlist',
        text: $gettext('Playlist (M3U/PLS) URL')
    }
];

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
