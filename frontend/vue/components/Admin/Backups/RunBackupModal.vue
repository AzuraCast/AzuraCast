<template>
    <b-modal size="md" centered id="run_backup_modal" ref="modal" :title="langTitle"
             @hidden="clearContents">
        <template #default="slotProps">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

            <b-form v-if="logUrl === null" class="form vue-form" @submit.prevent="submit">
                <b-form-fieldset>
                    <b-form-row>
                        <b-wrapped-form-group class="col-md-12" id="edit_form_storage_location"
                                              :field="v$.form.storage_location">
                            <template #label="{lang}">
                                {{ $gettext('Storage Location') }}
                            </template>
                            <template #default="props">
                                <b-form-select :id="props.id" v-model="props.field.$model"
                                               :options="storageLocationOptions"></b-form-select>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group class="col-md-12" id="edit_form_path"
                                              :field="v$.form.path">
                            <template #label="{lang}">
                                {{ $gettext('File Name') }}
                            </template>
                            <template #description="{lang}">
                                {{ $gettext('This will be the file name for your backup, include the extension for file type you wish to use.') }}
                                <br>
                                <strong>
                                    {{ $gettext('Supported file formats:') }}
                                </strong>
                                <br>
                                <ul class="m-0">
                                    <li>.zip</li>
                                    <li>.tar.gz</li>
                                    <li>.tzst (
                                        {{ $gettext('ZStandard compression') }}
                                        )
                                    </li>
                                </ul>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-checkbox class="col-md-12" id="edit_form_exclude_media"
                                                 :field="v$.form.exclude_media">
                            <template #label="{lang}">
                                {{ $gettext('Exclude Media from Backup') }}
                            </template>
                            <template #description="{lang}">
                                {{ $gettext('This will produce a significantly smaller backup, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.') }}
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
                    {{ $gettext('Close') }}
                </b-button>
                <b-button v-if="logUrl === null" :variant="(v$.form.$invalid) ? 'danger' : 'primary'" type="submit"
                          @click="submit">
                    {{ $gettext('Run Manual Backup') }}
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script>
import useVuelidate from "@vuelidate/core";
import BFormFieldset from "~/components/Form/BFormFieldset";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";
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
    setup() {
        return {v$: useVuelidate()}
    },
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
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
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
