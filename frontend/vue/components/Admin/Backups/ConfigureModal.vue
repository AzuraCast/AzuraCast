<template>
    <modal-form ref="modal" size="lg" :title="langTitle" :loading="loading" :disable-save-button="v$.form.$invalid"
                @submit="submit" @hidden="clearContents">
        <b-form-fieldset>
            <div class="form-row mb-3">
                <b-wrapped-form-checkbox class="col-md-12" id="form_edit_backup_enabled"
                                         :field="v$.form.backup_enabled">
                    <template #label>
                        {{ $gettext('Run Automatic Nightly Backups') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Enable to have AzuraCast automatically run nightly backups at the time specified.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>
            </div>

            <div class="form-row" v-if="v$.form.backup_enabled.$model">
                <b-wrapped-form-group class="col-md-6" id="form_backup_time_code" :field="v$.form.backup_time_code">
                    <template #label>
                        {{ $gettext('Scheduled Backup Time') }}
                    </template>
                    <template #description>
                        {{ $gettext('If the end time is before the start time, the playlist will play overnight.') }}
                    </template>
                    <template #default="props">
                        <time-code :id="props.id" v-model="props.field.$model" :state="props.state"></time-code>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-checkbox class="col-md-6" id="form_edit_exclude_media"
                                         :field="v$.form.backup_exclude_media">
                    <template #label>
                        {{ $gettext('Exclude Media from Backup') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Excluding media from automated backups will save space, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.')
                        }}
                    </template>
                </b-wrapped-form-checkbox>

                <b-wrapped-form-group class="col-md-6" id="form_backup_keep_copies" :field="v$.form.backup_keep_copies"
                                      input-type="number" :input-attrs="{min: '0', max: '365'}">
                    <template #label>
                        {{ $gettext('Number of Backup Copies to Keep') }}
                    </template>
                    <template #description>
                        {{
                            $gettext('Copies older than the specified number of days will automatically be deleted. Set to zero to disable automatic deletion.')
                        }}
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_backup_storage_location"
                                      :field="v$.form.backup_storage_location">
                    <template #label>
                        {{ $gettext('Storage Location') }}
                    </template>
                    <template #default="props">
                        <b-form-select :id="props.id" v-model="props.field.$model"
                                       :options="storageLocationOptions"></b-form-select>
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group class="col-md-6" id="edit_form_backup_format" :field="v$.form.backup_format">
                    <template #label>
                        {{ $gettext('Backup Format') }}
                    </template>
                    <template #default="props">
                        <b-form-radio-group stacked :id="props.id" v-model="props.field.$model"
                                            :options="formatOptions"></b-form-radio-group>
                    </template>
                </b-wrapped-form-group>
            </div>
        </b-form-fieldset>
    </modal-form>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import CodemirrorTextarea from "~/components/Common/CodemirrorTextarea";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";
import BFormFieldset from "~/components/Form/BFormFieldset";
import mergeExisting from "~/functions/mergeExisting";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import TimeCode from "~/components/Common/TimeCode";
import objectToFormOptions from "~/functions/objectToFormOptions";

export default {
    name: 'AdminBackupsConfigureModal',
    emits: ['relist'],
    setup() {
        return {v$: useVuelidate()}
    },
    props: {
        settingsUrl: String,
        storageLocations: Object
    },
    components: {
        ModalForm,
        BFormFieldset,
        BWrappedFormGroup,
        BWrappedFormCheckbox,
        CodemirrorTextarea,
        TimeCode
    },
    data() {
        return {
            loading: true,
            error: null,
            form: {},
        };
    },
    validations: {
        form: {
            'backup_enabled': {},
            'backup_time_code': {},
            'backup_exclude_media': {},
            'backup_keep_copies': {},
            'backup_storage_location': {},
            'backup_format': {},
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Configure Backups');
        },
        storageLocationOptions() {
            return objectToFormOptions(this.storageLocations);
        },
        formatOptions() {
            return [
                {
                    value: 'zip',
                    text: 'Zip',
                },
                {
                    value: 'tgz',
                    text: 'TarGz'
                },
                {
                    value: 'tzst',
                    text: 'ZStd'
                }
            ];
        },
    },
    methods: {
        open() {
            this.clearContents();
            this.loading = true;

            this.$refs.modal.show();

            this.axios.get(this.settingsUrl).then((resp) => {
                this.form = mergeExisting(this.form, resp.data);
                this.loading = false;
            }).catch(() => {
                this.close();
            });
        },
        clearContents() {
            this.v$.$reset();

            this.form = {
                backup_enabled: false,
                backup_time_code: null,
                backup_exclude_media: null,
                backup_keep_copies: null,
                backup_storage_location: null,
                backup_format: null,
            };
        },
        close() {
            this.$emit('relist');
            this.$refs.modal.hide();
        },
        submit() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
                return;
            }

            this.$wrapWithLoading(
                this.axios({
                    method: 'PUT',
                    url: this.settingsUrl,
                    data: this.form
                })
            ).then(() => {
                this.$notifySuccess();
                this.close();
            });
        }
    }
}
</script>
