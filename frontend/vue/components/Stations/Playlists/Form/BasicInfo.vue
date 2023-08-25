<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
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
            />
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
                        <template #description>
                            <a
                                href="https://docs.azuracast.com/en/user-guide/playlists/advanced-playlists"
                                target="_blank"
                            >
                                {{ $gettext('Learn about Advanced Playlists') }}
                            </a>
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
                    />
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
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import FormFieldset from "~/components/Form/FormFieldset.vue";
import {map, range} from "lodash";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useTranslate} from "~/vendor/gettext";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import Tab from "~/components/Common/Tab.vue";

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
    form,
    {
        name: '',
        is_enabled: true,
        include_in_on_demand: false,
        weight: 3,
        type: 'default',
        source: 'songs',
        order: 'shuffle',
        remote_url: null,
        remote_type: 'stream',
        remote_buffer: 0,
        is_jingle: false,
        play_per_songs: 0,
        play_per_minutes: 0,
        play_per_hour_minute: 0,
        include_in_requests: true,
        avoid_duplicates: true,
    }
);

const {$gettext} = useTranslate();

const sourceOptions = [
    {
        value: 'songs',
        text: $gettext('Song-Based'),
        description: $gettext('A playlist containing media files hosted on this server.')
    },
    {
        value: 'remote_url',
        text: $gettext('Remote URL'),
        description: $gettext('A playlist that instructs the station to play from a remote URL.')
    }
];

const typeOptions = [
    {
        value: 'default',
        text: $gettext('General Rotation'),
        description: $gettext('Standard playlist, shuffles with other standard playlists based on weight.')
    },
    {
        value: 'once_per_x_songs',
        text: $gettext('Once per x Songs'),
        description: $gettext('Play once every $x songs.')
    },
    {
        value: 'once_per_x_minutes',
        text: $gettext('Once per x Minutes'),
        description: $gettext('Play once every $x minutes.')
    },
    {
        value: 'once_per_hour',
        text: $gettext('Once per Hour'),
        description: $gettext('Play once per hour at the specified minute.')
    },
    {
        value: 'custom',
        text: $gettext('Advanced'),
        description: $gettext('Manually define how this playlist is used in Liquidsoap configuration.')
    }
];

const orderOptions = [
    {
        value: 'shuffle',
        text: $gettext('Shuffled'),
        description: $gettext('The full playlist is shuffled and then played through in the shuffled order.')
    },
    {
        value: 'random',
        text: $gettext('Random'),
        description: $gettext('A completely random track is picked for playback every time the queue is populated.')
    },
    {
        value: 'sequential',
        text: $gettext('Sequential'),
        description: $gettext('The order of the playlist is manually specified and followed by the AutoDJ.')
    }
];

const remoteTypeOptions = [
    {
        value: 'stream',
        text: $gettext('Icecast/Shoutcast Stream URL')
    },
    {
        value: 'playlist',
        text: $gettext('Playlist (M3U/PLS) URL')
    },
    {
        value: 'other',
        text: $gettext('Other Remote URL (File, HLS, etc.)')
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
