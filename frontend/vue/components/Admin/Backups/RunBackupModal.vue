<template>
    <b-modal size="md" centered id="run_backup_modal" ref="modal" :title="langTitle"
             @hidden="clearContents">
        <template #default="slotProps">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-form v-if="logUrl === null" class="form vue-form" @submit.prevent="submit">
                <b-form-fieldset>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-12" id="edit_form_storage_location"
                                              :field="$v.form.storage_location">
                            <template #label="{lang}">
                                <translate :key="lang">Storage Location</translate>
                            </template>
                            <template #default="props">
                                <b-form-select :id="props.id" v-model="props.field.$model"
                                               :options="storageLocationOptions"></b-form-select>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12" id="edit_form_path"
                                              :field="$v.form.path">
                            <template #label="{lang}">
                                <translate :key="lang">File Name</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">This will be the file name for your backup, include the extension for file type you wish to use.</translate>
                                <br>
                                <strong>
                                    <translate :key="lang+'2'">Supported file formats:</translate>
                                </strong>
                                <br>
                                <ul class="m-0">
                                    <li>.zip</li>
                                    <li>.tar.gz</li>
                                    <li>.tzst (
                                        <translate :key="lang+'zstd'">ZStandard compression</translate>
                                        )
                                    </li>
                                </ul>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-checkbox class="col-md-12" id="edit_form_exclude_media"
                                                 :field="$v.form.exclude_media">
                            <template #label="{lang}">
                                <translate :key="lang">Exclude Media from Backup</translate>
                            </template>
                            <template #description="{lang}">
                                <translate :key="lang">This will produce a significantly smaller backup, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.</translate>
                            </template>
                        </b-wrapped-form-checkbox>
                    </b-form-row>
                </b-form-fieldset>

                <invisible-submit-button/>
            </b-form>

            <div v-else>
                <streaming-log-view :log-url="logUrl"></streaming-log-view>
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot name="modal-footer" v-bind="slotProps">
                <b-button variant="default" type="button" @click="close">
                    <translate key="lang_btn_close">Close</translate>
                </b-button>
                <b-button v-if="logUrl === null" :variant="($v.form.$invalid) ? 'danger' : 'primary'" type="submit"
                          @click="submit">
                    <translate key="lang_btn_run_backup">Run Manual Backup</translate>
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script>
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";
import {validationMixin} from "vuelidate";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox";
import objectToFormOptions from "~/functions/objectToFormOptions";
import StreamingLogView from "~/components/Common/StreamingLogView";

export default {
    name: 'AdminBackupsRunBackupModal',
    emits: ['relist'],
    props: {
        runBackupUrl: String,
        storageLocations: Object
    },
    mixins: [
        validationMixin
    ],
    components: {
        BFormFieldset,
        BWrappedFormGroup,
        BWrappedFormCheckbox,
        InvisibleSubmitButton,
        StreamingLogView
    },
    validations: {
        form: {
            'storage_location': {},
            'path': {},
            'exclude_media': {}
        }
    },
    computed: {
        langTitle() {
            return this.$gettext('Run Manual Backup');
        },
        storageLocationOptions() {
            return objectToFormOptions(this.storageLocations);
        }
    },
    data() {
        return {
            logUrl: null,
            error: null,
            form: {},
        };
    },
    methods: {
        open() {
            this.$refs.modal.show();
        },
        close() {
            this.$refs.modal.hide();
            this.$emit('relist');
        },
        submit() {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            this.error = null;
            this.$wrapWithLoading(
                this.axios({
                    method: 'POST',
                    url: this.runBackupUrl,
                    data: this.form
                })
            ).then((resp) => {
                this.logUrl = resp.data.links.log;
            }).catch((error) => {
                this.error = error.response.data.message;
            });
        },
        clearContents() {
            this.logUrl = null;
            this.error = null;

            this.form = {
                storage_location: null,
                path: null,
                exclude_media: false
            };
        }
    }
}
</script>
