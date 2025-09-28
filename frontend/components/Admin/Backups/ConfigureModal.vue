<template>
    <modal-form
        ref="$modal"
        size="lg"
        :title="$gettext('Configure Backups')"
        :loading="loading"
        :disable-save-button="r$.$invalid"
        @submit="submit"
        @hidden="resetForm"
    >
        <form-fieldset>
            <div class="row g-3 mb-3">
                <form-group-checkbox
                    id="form_edit_backup_enabled"
                    class="col-md-12"
                    :field="r$.backup_enabled"
                    :label="$gettext('Run Automatic Nightly Backups')"
                    :description="$gettext('Enable to have AzuraCast automatically run nightly backups at the time specified.')"
                />
            </div>

            <div
                v-if="form.backup_enabled"
                class="row g-3"
            >
                <form-group-field
                    id="form_backup_time_code"
                    class="col-md-6"
                    :field="r$.backup_time_code"
                    :label="$gettext('Scheduled Backup Time')"
                    :description="$gettext('Backup times are always in UTC.')"
                >
                    <template #default="{id, model, fieldClass}">
                        <time-code
                            :id="id"
                            v-model="model.$model"
                            :class="fieldClass"
                        />
                    </template>
                </form-group-field>

                <form-group-checkbox
                    id="form_edit_exclude_media"
                    class="col-md-6"
                    :field="r$.backup_exclude_media"
                    :label="$gettext('Exclude Media from Backup')"
                    :description="$gettext('Excluding media from automated backups will save space, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.')"
                />

                <form-group-field
                    id="form_backup_keep_copies"
                    class="col-md-6"
                    :field="r$.backup_keep_copies"
                    input-type="number"
                    :input-attrs="{min: '0', max: '365'}"
                    :label="$gettext('Number of Backup Copies to Keep')"
                    :description="$gettext('Copies older than the specified number of days will automatically be deleted. Set to zero to disable automatic deletion.')"
                />

                <form-group-select
                    id="edit_form_backup_storage_location"
                    class="col-md-6"
                    :field="r$.backup_storage_location"
                    :label="$gettext('Storage Location')"
                    :options="storageLocations"
                />

                <form-group-multi-check
                    id="edit_form_backup_format"
                    class="col-md-6"
                    :field="r$.backup_format"
                    stacked
                    radio
                    :options="formatOptions"
                    :label="$gettext('Backup Format')"
                />
            </div>
        </form-fieldset>
    </modal-form>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import FormFieldset from "~/components/Form/FormFieldset.vue";
import mergeExisting from "~/functions/mergeExisting";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import TimeCode from "~/components/Common/TimeCode.vue";
import {computed, ref, useTemplateRef} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import {BackupSettings} from "~/components/Admin/BackupsWrapper.vue";

const props = defineProps<{
    settingsUrl: string,
    storageLocations: Record<number, string>,
}>();

const emit = defineEmits<HasRelistEmit>();

const loading = ref(true);

type BackupSettingsRow = Omit<BackupSettings, 'backup_last_run' | 'backup_last_output'>;

const form = ref<BackupSettingsRow>({
    backup_enabled: false,
    backup_time_code: null,
    backup_exclude_media: false,
    backup_keep_copies: 2,
    backup_storage_location: null,
    backup_format: null
});

const {r$} = useAppRegle(
    form,
    {},
    {}
);

const resetForm = () => r$.$reset({
    toOriginalState: true
});

const formatOptions = computed(() => {
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
});

const {axios} = useAxios();

const $modal = useTemplateRef('$modal');
const {hide, show} = useHasModal($modal);

const close = () => {
    emit('relist');
    hide();
};

const doOpen = async () => {
    resetForm();

    loading.value = true;

    show();

    try {
        const {data} = await axios.get(props.settingsUrl);

        r$.$reset({
            toState: mergeExisting(r$.$value, data)
        });
        loading.value = false;
    } catch {
        close();
    }
};

const open = () => {
    void doOpen();
};

const {notifySuccess} = useNotify();

const submit = async () => {
    const {valid, data: postData} = await r$.$validate();
    if (!valid) {
        return;
    }

    await axios({
        method: 'PUT',
        url: props.settingsUrl,
        data: postData
    });

    notifySuccess();
    close();
}

defineExpose({
    open
});
</script>
