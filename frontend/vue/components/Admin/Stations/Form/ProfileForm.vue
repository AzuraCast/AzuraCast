<template>
    <b-form-fieldset>
        <div class="form-row">
            <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
                <template #label>
                    {{ $gettext('Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_description" :field="form.description"
                                  input-type="textarea">
                <template #label>
                    {{ $gettext('Description') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_genre" :field="form.genre">
                <template #label>
                    {{ $gettext('Genre') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_url" :field="form.url" input-type="url">
                <template #label>
                    {{ $gettext('Web Site URL') }}
                </template>
                <template #description>
                    {{
                        $gettext('Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-12" id="edit_form_timezone" :field="form.timezone">
                <template #label>
                    {{ $gettext('Time Zone') }}
                </template>
                <template #description>
                    {{
                        $gettext('Scheduled playlists and other timed items will be controlled by this time zone.')
                    }}
                </template>
                <template #default="props">
                    <b-form-select :id="props.id" v-model="props.field.$model"
                                   :options="timezoneOptions"></b-form-select>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_default_album_art_url"
                                  :field="form.default_album_art_url">
                <template #label>
                    {{ $gettext('Default Album Art URL') }}
                </template>
                <template #description>
                    {{
                        $gettext('If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group v-if="showAdvanced" class="col-md-6" id="edit_form_short_name"
                                  :field="form.short_name" advanced>
                <template #label>
                    {{ $gettext('URL Stub') }}
                </template>
                <template #description>
                    {{
                        $gettext('Optionally specify a short URL-friendly name, such as "my_station_name", that will be used in this station\'s URLs. Leave this field blank to automatically create one based on the station name.')
                    }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group v-if="showAdvanced" class="col-md-6" id="edit_form_api_history_items"
                                  :field="form.api_history_items" advanced>
                <template #label>
                    {{ $gettext('Number of Visible Recent Songs') }}
                </template>
                <template #description>
                    {{
                        $gettext('Customize the number of songs that will appear in the "Song History" section for this station and in all public APIs.')
                    }}
                </template>
                <template #default="props">
                    <b-form-select :id="props.id" v-model="props.field.$model"
                                   :options="historyItemsOptions"></b-form-select>
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-fieldset>

    <b-form-fieldset>
        <template #label>
            {{ $gettext('Public Pages') }}
        </template>

        <div class="form-row">
            <b-wrapped-form-checkbox class="col-md-12" id="edit_form_enable_public_page"
                                     :field="form.enable_public_page">
                <template #label>
                    {{ $gettext('Enable Public Pages') }}
                </template>
                <template #description>
                    {{ $gettext('Show the station in public pages and general API results.') }}
                </template>
            </b-wrapped-form-checkbox>
        </div>
    </b-form-fieldset>

    <b-form-fieldset>
        <template #label>
            {{ $gettext('On-Demand Streaming') }}
        </template>

        <div class="form-row">
            <b-wrapped-form-checkbox class="col-md-12" id="edit_form_enable_on_demand"
                                     :field="form.enable_on_demand">
                <template #label>
                    {{ $gettext('Enable On-Demand Streaming') }}
                </template>
                <template #description>
                    {{
                        $gettext('If enabled, music from playlists with on-demand streaming enabled will be available to stream via a specialized public page.')
                    }}
                </template>
            </b-wrapped-form-checkbox>

            <b-wrapped-form-checkbox v-if="form.enable_on_demand.$model" class="col-md-12"
                                     id="edit_form_enable_on_demand_download"
                                     :field="form.enable_on_demand_download">
                <template #label>
                    {{ $gettext('Enable Downloads on On-Demand Page') }}
                </template>
                <template #description>
                    {{
                        $gettext('If enabled, a download button will also be present on the public "On-Demand" page.')
                    }}
                </template>
            </b-wrapped-form-checkbox>
        </div>
    </b-form-fieldset>
</template>

<script setup>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import objectToFormOptions from "~/functions/objectToFormOptions";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import {computed} from "vue";
import gettext from "~/vendor/gettext";

const props = defineProps({
    form: Object,
    timezones: Object,
    showAdvanced: {
        type: Boolean,
        default: true
    },
});

const timezoneOptions = computed(() => {
    return objectToFormOptions(props.timezones);
});

const {$gettext} = gettext;

const historyItemsOptions = computed(() => {
    return [
        {
            text: $gettext('Disabled'),
            value: 0,
        },
        {text: '1', value: 1},
        {text: '5', value: 5},
        {text: '10', value: 10},
        {text: '15', value: 15}
    ];
});
</script>
