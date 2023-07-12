<template>
    <modal
        id="run_backup_modal"
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Run Manual Backup')"
        @hidden="clearContents"
    >
        <template #default>
            <div
                v-show="error != null"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <form
                v-if="logUrl === null"
                class="form vue-form"
                @submit.prevent="submit"
            >
                <form-fieldset>
                    <div class="row g-3">
                        <form-group-select
                            id="edit_form_storage_location"
                            class="col-md-12"
                            :field="v$.storage_location"
                            :options="storageLocationOptions"
                            :label="$gettext('Storage Location')"
                        />

                        <form-group-field
                            id="edit_form_path"
                            class="col-md-12"
                            :field="v$.path"
                            :label="$gettext('File Name')"
                        >
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
                        </form-group-field>

                        <form-group-checkbox
                            id="edit_form_exclude_media"
                            class="col-md-12"
                            :field="v$.exclude_media"
                            :label="$gettext('Exclude Media from Backup')"
                            :description="$gettext('This will produce a significantly smaller backup, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.')"
                        />
                    </div>
                </form-fieldset>

                <invisible-submit-button />
            </form>

            <div v-else>
                <streaming-log-view :log-url="logUrl" />
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="close"
                >
                    {{ $gettext('Close') }}
                </button>
                <button
                    v-if="logUrl === null"
                    class="btn"
                    :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                    type="submit"
                    @click="submit"
                >
                    {{ $gettext('Run Manual Backup') }}
                </button>
            </slot>
        </template>
    </modal>
</template>

<script setup>
import FormFieldset from "~/components/Form/FormFieldset";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import StreamingLogView from "~/components/Common/StreamingLogView.vue";
import {computed, ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import Modal from "~/components/Common/Modal.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";

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
