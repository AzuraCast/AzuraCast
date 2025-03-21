<template>
    <modal
        id="run_backup_modal"
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Run Manual Backup')"
        @hidden="onHidden"
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
                            :options="storageLocations"
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
                                    $gettext('This will be the file name for your backup, include the extension for file type you wish to use. Leave blank to have a name generated automatically.')
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
                    @click="hide"
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

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import StreamingLogView from "~/components/Common/StreamingLogView.vue";
import {ref, useTemplateRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import Modal from "~/components/Common/Modal.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {ApiTaskWithLog} from "~/entities/ApiInterfaces.ts";

const props = defineProps<{
    runBackupUrl: string,
    storageLocations: Record<number, string>,
}>();

const emit = defineEmits<HasRelistEmit>();

const logUrl = ref(null);
const error = ref(null);

const $modal = useTemplateRef('$modal');
const {show: open, hide} = useHasModal($modal);

const {form, resetForm, v$, validate} = useVuelidateOnForm(
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

const {axios} = useAxios();

const submit = async () => {
    const isValid = await validate();
    if (!isValid) {
        return;
    }

    try {
        const {data} = await axios.post<ApiTaskWithLog>(props.runBackupUrl, form.value);
        logUrl.value = data.logUrl;
    } catch (error) {
        error.value = error.response.data.message
    }
};

const clearContents = () => {
    logUrl.value = null;
    error.value = null;

    resetForm();
}

const onHidden = () => {
    clearContents();
    emit('relist');
}

defineExpose({
    open
});
</script>
