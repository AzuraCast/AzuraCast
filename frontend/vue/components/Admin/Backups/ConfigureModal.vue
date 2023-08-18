<template>
    <modal-form
        ref="$modal"
        size="lg"
        :title="$gettext('Configure Backups')"
        :loading="loading"
        :disable-save-button="v$.$invalid"
        @submit="submit"
        @hidden="resetForm"
    >
        <form-fieldset>
            <div class="row g-3 mb-3">
                <form-group-checkbox
                    id="form_edit_backup_enabled"
                    class="col-md-12"
                    :field="v$.backup_enabled"
                    :label="$gettext('Run Automatic Nightly Backups')"
                    :description="$gettext('Enable to have AzuraCast automatically run nightly backups at the time specified.')"
                />
            </div>

            <div
                v-if="v$.backup_enabled.$model"
                class="row g-3"
            >
                <form-group-field
                    id="form_backup_time_code"
                    class="col-md-6"
                    :field="v$.backup_time_code"
                    :label="$gettext('Scheduled Backup Time')"
                    :description="$gettext('If the end time is before the start time, the playlist will play overnight.')"
                >
                    <template #default="slotProps">
                        <time-code
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            :class="slotProps.class"
                        />
                    </template>
                </form-group-field>

                <form-group-checkbox
                    id="form_edit_exclude_media"
                    class="col-md-6"
                    :field="v$.backup_exclude_media"
                    :label="$gettext('Exclude Media from Backup')"
                    :description="$gettext('Excluding media from automated backups will save space, but you should make sure to back up your media elsewhere. Note that only locally stored media will be backed up.')"
                />

                <form-group-field
                    id="form_backup_keep_copies"
                    class="col-md-6"
                    :field="v$.backup_keep_copies"
                    input-type="number"
                    :input-attrs="{min: '0', max: '365'}"
                    :label="$gettext('Number of Backup Copies to Keep')"
                    :description="$gettext('Copies older than the specified number of days will automatically be deleted. Set to zero to disable automatic deletion.')"
                />

                <form-group-select
                    id="edit_form_backup_storage_location"
                    class="col-md-6"
                    :field="v$.backup_storage_location"
                    :label="$gettext('Storage Location')"
                    :options="storageLocationOptions"
                />

                <form-group-multi-check
                    id="edit_form_backup_format"
                    class="col-md-6"
                    :field="v$.backup_format"
                    stacked
                    radio
                    :options="formatOptions"
                    :label="$gettext('Backup Format')"
                />
            </div>
        </form-fieldset>
    </modal-form>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import FormFieldset from "~/components/Form/FormFieldset";
import mergeExisting from "~/functions/mergeExisting";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import TimeCode from "~/components/Common/TimeCode.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed, ref} from "vue";
import {useAxios} from "~/vendor/axios";
import {useNotify} from "~/functions/useNotify";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupSelect from "~/components/Form/FormGroupSelect.vue";

const props = defineProps({
    settingsUrl: {
        type: String,
        required: true
    },
    storageLocations: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['relist']);

const loading = ref(true);

const $modal = ref(); // ModalForm

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        'backup_enabled': {},
        'backup_time_code': {},
        'backup_exclude_media': {},
        'backup_keep_copies': {},
        'backup_storage_location': {},
        'backup_format': {},
    },
    {
        backup_enabled: false,
        backup_time_code: null,
        backup_exclude_media: null,
        backup_keep_copies: null,
        backup_storage_location: null,
        backup_format: null,
    }
);

const storageLocationOptions = computed(() => {
    return objectToFormOptions(props.storageLocations);
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

const close = () => {
    emit('relist');
    $modal.value.hide();
};

const open = () => {
    resetForm();
    loading.value = true;

    $modal.value.show();

    axios.get(props.settingsUrl).then((resp) => {
        form.value = mergeExisting(form.value, resp.data);
        loading.value = false;
    }).catch(() => {
        close();
    });
};

const {wrapWithLoading, notifySuccess} = useNotify();

const submit = () => {
    ifValid(() => {
        wrapWithLoading(
            axios({
                method: 'PUT',
                url: props.settingsUrl,
                data: form.value
            })
        ).then(() => {
            notifySuccess();
            close();
        });
    });
}

defineExpose({
    open
});
</script>
