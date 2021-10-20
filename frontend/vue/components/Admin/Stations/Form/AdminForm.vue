<template>
    <b-tab :title="langTabTitle" :title-link-class="tabClass">
        <b-form-group>
            <b-row>
                <b-wrapped-form-group class="col-md-6" id="edit_form_is_enabled" :field="form.is_enabled">
                    <template #description>
                        <translate key="lang_edit_form_is_enabled_desc">If disabled, the station will not broadcast or shuffle its AutoDJ.</translate>
                    </template>
                    <template #default="props">
                        <b-form-checkbox :id="props.id" v-model="props.field.$model">
                            <translate
                                key="lang_edit_form_is_enabled">Enable Broadcasting</translate>
                        </b-form-checkbox>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_radio_base_dir" :field="form.radio_base_dir"
                                      advanced>
                    <template #label>
                        <translate key="lang_edit_form_radio_base_dir">Base Station Directory</translate>
                    </template>
                    <template #description>
                        <translate key="lang_edit_form_radio_base_dir_desc">The parent directory where station playlist and configuration files are stored. Leave blank to use default directory.</translate>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_media_storage_location_id"
                                      :field="form.media_storage_location_id">
                    <template #label>
                        <translate key="lang_form_media_storage_location_id">Media Storage Location</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model"
                                       :options="mediaStorageLocationOptions"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_recordings_storage_location_id"
                                      :field="form.recordings_storage_location_id">
                    <template #label>
                        <translate
                            key="lang_form_recordings_storage_location_id">Live Recordings Storage Location</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model"
                                       :options="recordingsStorageLocationOptions"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-12" id="edit_form_podcasts_storage_location_id"
                                      :field="form.podcasts_storage_location_id">
                    <template #label>
                        <translate key="lang_form_podcasts_storage_location_id">Podcasts Storage Location</translate>
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model"
                                       :options="podcastsStorageLocationOptions"></b-form-select>
                    </template>
                </b-wrapped-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import _ from "lodash";
import objectToFormOptions from "~/functions/objectToFormOptions";

export default {
    name: 'AdminStationsAdminForm',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        tabClass: {},
        isEditMode: Boolean,
        mediaStorageLocations: Object,
        recordingsStorageLocations: Object,
        podcastsStorageLocations: Object
    },
    computed: {
        langTabTitle() {
            return this.$gettext('Administration');
        },
        mediaStorageLocationOptions() {
            return objectToFormOptions(this.filterLocations(this.mediaStorageLocations));
        },
        recordingsStorageLocationOptions() {
            return objectToFormOptions(this.filterLocations(this.recordingsStorageLocations));
        },
        podcastsStorageLocationOptions() {
            return objectToFormOptions(this.filterLocations(this.podcastsStorageLocations));
        },
    },
    methods: {
        filterLocations(group) {
            if (!this.isEditMode) {
                return group;
            }

            let newGroup = _.clone(group);
            delete newGroup[""];
            return newGroup;
        }
    }
}
</script>
