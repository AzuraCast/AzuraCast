<template>
    <div>
        <b-form-group>
            <b-form-fieldset>
                <b-form-row>
                    <b-wrapped-form-checkbox class="col-md-6" id="edit_form_is_enabled" :field="form.is_enabled">
                        <template #label="{lang}">
                            <translate :key="lang">Enable Broadcasting</translate>
                        </template>
                        <template #description="{lang}">
                            <translate
                                :key="lang">If disabled, the station will not broadcast or shuffle its AutoDJ.</translate>
                        </template>
                    </b-wrapped-form-checkbox>

                    <b-wrapped-form-group v-if="showAdvanced" class="col-md-6" id="edit_form_radio_base_dir"
                                          :field="form.radio_base_dir" advanced>
                        <template #label="{lang}">
                            <translate :key="lang">Base Station Directory</translate>
                        </template>
                        <template #description="{lang}">
                            <translate :key="lang">The parent directory where station playlist and configuration files are stored. Leave blank to use default directory.</translate>
                        </template>
                    </b-wrapped-form-group>
                </b-form-row>
            </b-form-fieldset>

            <b-form-fieldset>
                <b-overlay variant="card" :show="storageLocationsLoading">
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-12" id="edit_form_media_storage_location"
                                              :field="form.media_storage_location">
                            <template #label="{lang}">
                                <translate :key="lang">Media Storage Location</translate>
                            </template>
                            <template #default="props">
                                <b-form-select :id="props.id" v-model="props.field.$model"
                                               :options="storageLocationOptions.media_storage_location"></b-form-select>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12" id="edit_form_recordings_storage_location"
                                              :field="form.recordings_storage_location">
                            <template #label="{lang}">
                                <translate :key="lang">Live Recordings Storage Location</translate>
                            </template>
                            <template #default="props">
                                <b-form-select :id="props.id" v-model="props.field.$model"
                                               :options="storageLocationOptions.recordings_storage_location"></b-form-select>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12" id="edit_form_podcasts_storage_location"
                                              :field="form.podcasts_storage_location">
                            <template #label="{lang}">
                                <translate :key="lang">Podcasts Storage Location</translate>
                            </template>
                            <template #default="props">
                                <b-form-select :id="props.id" v-model="props.field.$model"
                                               :options="storageLocationOptions.podcasts_storage_location"></b-form-select>
                            </template>
                        </b-wrapped-form-group>
                    </b-form-row>
                </b-overlay>
            </b-form-fieldset>
        </b-form-group>
    </div>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import objectToFormOptions from "~/functions/objectToFormOptions";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import BFormFieldset from "~/components/Form/BFormFieldset";

export default {
    name: 'AdminStationsAdminForm',
    components: {BWrappedFormCheckbox, BWrappedFormGroup, BFormFieldset},
    props: {
        form: Object,
        isEditMode: Boolean,
        storageLocationApiUrl: String,
        showAdvanced: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            storageLocationsLoading: true,
            storageLocationOptions: {
                media_storage_location: [],
                recordings_storage_location: [],
                podcasts_storage_location: []
            }
        }
    },
    mounted() {
        this.loadLocations();
    },
    methods: {
        loadLocations() {
            this.axios.get(this.storageLocationApiUrl).then((resp) => {
                this.storageLocationOptions.media_storage_location = objectToFormOptions(
                    this.filterLocations(resp.data.media_storage_location)
                );
                this.storageLocationOptions.recordings_storage_location = objectToFormOptions(
                    this.filterLocations(resp.data.recordings_storage_location)
                );
                this.storageLocationOptions.podcasts_storage_location = objectToFormOptions(
                    this.filterLocations(resp.data.podcasts_storage_location)
                );
            }).finally(() => {
                this.storageLocationsLoading = false;
            });
        },
        filterLocations(group) {
            if (!this.isEditMode) {
                return group;
            }

            let newGroup = {};
            for (const oldKey in group) {
                if (oldKey !== "") {
                    newGroup[oldKey] = group[oldKey];
                }
            }
            return newGroup;
        }
    }
}
</script>
