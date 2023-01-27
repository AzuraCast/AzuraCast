<template>
    <b-modal
        id="run_backup_modal"
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Run Manual Backup')"
        @hidden="clearContents"
    >
        <template #default>
            <b-alert
                variant="danger"
                :show="error != null"
            >
                {{ error }}
            </b-alert>

            <b-form
                v-if="logUrl === null"
                class="form vue-form"
                @submit.prevent="submit"
            >
                <b-form-fieldset>
                    <div class="form-row">
                        <b-wrapped-form-group
                            id="edit_form_storage_location"
                            class="col-md-12"
                            :field="v$.storage_location"
                        >
                            <template #label>
                                {{ $gettext('Storage Location') }}
                            </template>
                            <template #default="slotProps">
                                <b-form-select
                                    :id="slotProps.id"
                                    v-model="slotProps.field.$model"
                                    :options="storageLocationOptions"
                                />
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-group
                            id="edit_form_path"
                            class="col-md-12"
                            :field="v$.path"
                        >
                            <template #label>
                                {{ $gettext('File Name') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('This will be the file name for your backup, include the extension for file type you wish to use.')
                                }}
                                <br>
                                <strong>
                                    {{ $gettext('Supported file formats:') }}
                                </strong>
                                <br>
                                <ul class="m-0">
                                    <li>.zip</li>
                                    <li>.tar.gz</li>
                                    <li>
                                        .tzst (
                                        {{ $gettext('ZStandard compression') }}
                                        )
                                    </li>
                                </ul>
                            </template>
                        </b-wrapped-form-group>

                        <b-wrapped-form-checkbox
                            id="edit_form_exclude_media"
                            class="col-md-12"
                            :field="v$.exclude_media"
                        >
                            <template #label>
                                {{ $gettext('Exclude Media from Backup') }}
                            </template>
                            <template #description>
                                {{
                                    $gettext('This will produce a significantly smaller backup, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.')
                                }}
                            </template>
                        </b-wrapped-form-checkbox>
                    </div>
                </b-form-fieldset>

                <invisible-submit-button />
            </b-form>

            <div v-else>
                <streaming-log-view :log-url="logUrl" />
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <b-button
                    variant="default"
                    type="button"
                    @click="close"
                >
                    {{ $gettext('Close') }}
                </b-button>
                <b-button
                    v-if="logUrl === null"
                    :variant="(v$.$invalid) ? 'danger' : 'primary'"
                    type="submit"
                    @click="submit"
                >
                    {{ $gettext('Run Manual Backup') }}
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script setup>
import BFormFieldset from "~/components/Form/BFormFieldset.vue";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import BWrappedFormCheckbox from "~/components/Form/BWrappedFormCheckbox.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import StreamingLogView from "~/components/Common/StreamingLogView.vue";
import {computed, ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {BModal} from "bootstrap-vue";

const props = defineProps({
    runBackupUrl: {
        type: String,
        required: true
    },
    storageLocations: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['relist']);

const storageLocationOptions = computed(() => {
    return objectToFormOptions(props.storageLocations);
});

const logUrl = ref(null);
const error = ref(null);
const $modal = ref(); // BModal

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        'storage_location': {},
        'path': {},
        'exclude_media': {}
    },
    {
        storage_location: null,
        path: '',
        exclude_media: false,
    }
);

const open = () => {
    $modal.value.show();
};

const close = () => {
    $modal.value.hide();
    emit('relist');
}

const {wrapWithLoading} = useNotify();
const {axios} = useAxios();

const submit = () => {
    ifValid(() => {
        error.value = null;
        wrapWithLoading(
            axios({
                method: 'POST',
                url: props.runBackupUrl,
                data: form.value
            })
        ).then((resp) => {
            logUrl.value = resp.data.links.log;
        }).catch((error) => {
            error.value = error.response.data.message;
        });
    });
};

const clearContents = () => {
    logUrl.value = null;
    error.value = null;

    resetForm();
}

defineExpose({
    open
});
</script>
