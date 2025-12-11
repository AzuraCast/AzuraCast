<template>
    <modal
        id="update_metadata"
        ref="$modal"
        centered
        :title="$gettext('Update Metadata')"
        @hidden="onHidden"
        @shown="onShown"
    >
        <info-card>
            {{
                $gettext('Use this form to send a manual metadata update. Note that this will override any existing metadata on the stream.')
            }}
        </info-card>

        <form @submit.prevent="doUpdateMetadata">
            <div class="row g-3">
                <form-group-field
                    id="update_metadata_title"
                    ref="$field"
                    class="col-12"
                    :field="r$.title"
                    :label="$gettext('Title')"
                />

                <form-group-field
                    id="update_metadata_artist"
                    class="col-12"
                    :field="r$.artist"
                    :label="$gettext('Artist')"
                />
            </div>

            <invisible-submit-button />
        </form>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="hide"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                type="button"
                class="btn"
                :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doUpdateMetadata"
            >
                {{ $gettext('Update Metadata') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {required} from "@regle/rules";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {nextTick, useTemplateRef} from "vue";
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import InfoCard from "~/components/Common/InfoCard.vue";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const {getStationApiUrl} = useApiRouter();

const updateMetadataUrl = getStationApiUrl('/nowplaying/update');

type UpdateMetadataRecord = {
    title: string,
    artist: string
}

const {record: form, reset: resetForm} = useResettableRef<UpdateMetadataRecord>({
    title: '',
    artist: ''
});

const {r$} = useAppRegle(
    form,
    {
        title: {required},
        artist: {}
    },
    {}
);

const $modal = useTemplateRef('$modal');
const {hide, show: open} = useHasModal($modal);

const onHidden = () => {
    resetForm();
    r$.$reset();
}

const $field = useTemplateRef('$field');

const onShown = () => {
    void nextTick(() => {
        $field.value?.focus();
    })
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doUpdateMetadata = async () => {
    const {valid} = await r$.$validate();
    if (!valid) {
        return;
    }

    try {
        await axios.post(updateMetadataUrl.value, form.value);

        notifySuccess($gettext('Metadata updated.'));
    } finally {
        hide();
    }
};

defineExpose({
    open
});
</script>
