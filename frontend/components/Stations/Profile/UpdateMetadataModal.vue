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
                    :field="v$.title"
                    :label="$gettext('Title')"
                />

                <form-group-field
                    id="update_metadata_artist"
                    class="col-12"
                    :field="v$.artist"
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
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doUpdateMetadata"
            >
                {{ $gettext('Update Metadata') }}
            </button>
        </template>
    </modal>
</template>

<script setup lang="ts">
import {required} from '@vuelidate/validators';
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {nextTick, ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import {ModalTemplateRef, useHasModal} from "~/functions/useHasModal.ts";
import {getStationApiUrl} from "~/router.ts";
import InfoCard from "~/components/Common/InfoCard.vue";
import {ComponentExposed} from "vue-component-type-helpers";

const updateMetadataUrl = getStationApiUrl('/nowplaying/update');

const {form, v$, resetForm, ifValid} = useVuelidateOnForm(
    {
        title: {required},
        artist: {}
    },
    {
        title: null,
        artist: null
    }
);

const $modal = ref<ModalTemplateRef>(null);
const {hide, show: open} = useHasModal($modal);

const onHidden = () => {
    resetForm();
}

const $field = ref<ComponentExposed<typeof FormGroupField> | null>(null);

const onShown = () => {
    nextTick(() => {
        $field.value?.focus();
    })
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doUpdateMetadata = () => {
    ifValid(() => {
        axios.post(updateMetadataUrl.value, form.value).then(() => {
            notifySuccess($gettext('Metadata updated.'));
        }).finally(() => {
            hide();
        });
    });
};

defineExpose({
    open
});
</script>
