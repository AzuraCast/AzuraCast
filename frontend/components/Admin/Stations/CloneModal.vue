<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Clone Station')"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <div class="row g-3">
            <form-group-field
                id="edit_form_name"
                class="col-md-12"
                :field="r$.name"
                :label="$gettext('New Station Name')"
            />

            <form-group-field
                id="edit_form_description"
                class="col-md-12"
                :field="r$.description"
                input-type="textarea"
                :label="$gettext('New Station Description')"
            />

            <form-group-multi-check
                id="edit_form_clone"
                class="col-md-12"
                :field="r$.clone"
                :options="cloneOptions"
                stacked
                :label="$gettext('Copy to New Station')"
            />
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import {required} from "@regle/rules";
import ModalForm from "~/components/Common/ModalForm.vue";
import {computed, ref, useTemplateRef} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {getErrorAsString, useAxios} from "~/vendor/axios";
import {HasRelistEmit} from "~/functions/useBaseEditModal.ts";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";

const emit = defineEmits<HasRelistEmit>();

const loading = ref(true);
const cloneUrl = ref<string | null>(null);
const error = ref<string | null>(null);

const {record: form, reset: resetForm} = useResettableRef({
    name: '',
    description: '',
    clone: [],
});

const {r$} = useAppRegle(
    form,
    {
        name: {required},
        description: {},
        clone: {}
    },
    {}
);

const $modal = useTemplateRef('$modal');
const {hide, show} = useHasModal($modal);

const {$gettext} = useTranslate();

const create = (stationName: string, stationCloneUrl: string) => {
    resetForm();
    r$.$reset();

    form.value.name = $gettext(
        '%{station} - Copy',
        {station: stationName}
    );
    loading.value = false;
    error.value = null;
    cloneUrl.value = stationCloneUrl;

    show();
};

const clearContents = () => {
    resetForm();
    cloneUrl.value = null;
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const cloneOptions = computed(() => {
    return [
        {
            text: $gettext('Share Media Storage Location'),
            value: 'media_storage'
        },
        {
            text: $gettext('Share Recordings Storage Location'),
            value: 'recordings_storage'
        },
        {
            text: $gettext('Share Podcasts Storage Location'),
            value: 'podcasts_storage'
        },
        {
            text: $gettext('Playlists'),
            value: 'playlists',
        },
        {
            text: $gettext('Mount Points'),
            value: 'mounts'
        },
        {
            text: $gettext('Remote Relays'),
            value: 'remotes'
        },
        {
            text: $gettext('Streamers/DJs'),
            value: 'streamers'
        },
        {
            text: $gettext('User Permissions'),
            value: 'permissions'
        },
        {
            text: $gettext('Web Hooks'),
            value: 'webhooks'
        }
    ];
});

const doSubmit = async () => {
    const {valid} = await r$.$validate();
    if (!valid) {
        return;
    }

    error.value = null;

    try {
        const currentCloneUrl = cloneUrl.value;
        if (currentCloneUrl === null) {
            return;
        }

        await axios({
            method: 'POST',
            url: currentCloneUrl,
            data: form.value
        });

        notifySuccess();
        emit('relist');
        hide();
    } catch (e) {
        error.value = getErrorAsString(e);
    }
};

defineExpose({
    create
});
</script>
