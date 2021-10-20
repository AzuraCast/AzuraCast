<template>
    <b-tab :title="langTabTitle" :title-link-class="tabClass" active>
        <b-form-fieldset>
            <b-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_name" :field="form.name">
                    <template #label>
                        <translate key="lang_form_name">Name</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_description" :field="form.description"
                                      input-type="textarea">
                    <template #label>
                        <translate key="lang_form_description">Description</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_genre" :field="form.genre">
                    <template #label>
                        <translate key="lang_form_genre">Genre</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_url" :field="form.url" input-type="url">
                    <template #label>
                        <translate key="lang_form_url">Web Site URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_url_desc">Note: This should be the public-facing homepage of the radio station, not the AzuraCast URL. It will be included in broadcast details.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_timezone" :field="form.timezone">
                    <template #label>
                        <translate key="lang_form_timezone">Time Zone</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_timezone_desc">Scheduled playlists and other timed items will be controlled by this time zone.</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model"
                                       :options="timezoneOptions"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_default_album_art_url"
                                      :field="form.default_album_art_url">
                    <template #label>
                        <translate key="lang_form_default_album_art_url">Default Album Art URL</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_default_album_art_url_desc">If a song has no album art, this URL will be listed instead. Leave blank to use the standard placeholder art.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_short_name" :field="form.short_name" advanced>
                    <template #label>
                        <translate key="lang_form_short_name">URL Stub</translate>
                    </template>
                    <template #description>
                    <translate
                        key="lang_form_short_name_desc">Optionally specify a short URL-friendly name, such as "my_station_name", that will be used in this station's URLs. Leave this field blank to automatically create one based on the station name.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_api_history_items" :field="form.api_history_items"
                                      advanced>
                    <template #label>
                        <translate key="lang_form_api_history_items">Number of Visible Recent Songs</translate>
                    </template>
                    <template #description>
                        <translate key="lang_form_api_history_items_desc">Customize the number of songs that will appear in the "Song History" section for this station and in all public APIs.</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model"
                                       :options="historyItemsOptions"></b-form-select>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_header_public_pages">Public Pages</translate>
            </template>

            <b-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_enable_public_page"
                                      :field="form.enable_public_page">
                    <template #description>
                        <translate key="lang_edit_form_enable_public_page_desc">Show the station in public pages and general API results.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_enable_public_page">Enable Public Pages</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-fieldset>

        <b-form-fieldset>
            <template #label>
                <translate key="lang_header_on_demand">On-Demand Streaming</translate>
            </template>

            <b-row>
                <b-wrapped-form-group class="col-md-12" id="edit_form_enable_on_demand"
                                      :field="form.enable_on_demand">
                    <template #description>
                        <translate key="lang_edit_form_enable_on_demand">If enabled, music from playlists with on-demand streaming enabled will be available to stream via a specialized public page.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_enable_on_demand">Enable On-Demand Streaming</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group v-if="form.enable_on_demand.$model" class="col-md-12"
                                      id="edit_form_enable_on_demand_download"
                                      :field="form.enable_on_demand_download">
                    <template #description>
                        <translate key="lang_edit_form_enable_on_demand">If enabled, a download button will also be present on the public "On-Demand" page.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_enable_on_demand_download">Enable Downloads on On-Demand Page</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-fieldset>
    </b-tab>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import objectToFormOptions from "~/functions/objectToFormOptions";

export default {
    name: 'AdminStationsProfileForm',
    components: {BWrappedFormGroup, BFormFieldset},
    props: {
        form: Object,
        tabClass: {},
        timezones: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Station Profile');
        },
        timezoneOptions() {
            return objectToFormOptions(this.timezones);
        },
        historyItemsOptions() {
            return [
                {
                    text: this.$gettext('Disabled'),
                    value: 0,
                },
                {text: '1', value: 1},
                {text: '5', value: 5},
                {text: '10', value: 10},
                {text: '15', value: 15}
            ];
        }
    }
}
</script>
