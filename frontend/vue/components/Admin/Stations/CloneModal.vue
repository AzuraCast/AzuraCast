<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Clone Station')"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <admin-stations-clone-modal-form :form="v$" />
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import ModalForm from "~/components/Common/ModalForm.vue";
import AdminStationsCloneModalForm from "~/components/Admin/Stations/CloneModalForm.vue";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";

const emit = defineEmits(['relist']);

const loading = ref(true);
const cloneUrl = ref(null);
const error = ref(null);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        name: {required},
        description: {},
        clone: {}
    },
    {
        name: '',
        description: '',
        clone: [],
    }
);

const $modal = ref(); // ModalForm
const {$gettext} = useTranslate();

const create = (stationName, stationCloneUrl) => {
    resetForm();

    form.value.name = $gettext(
        '%{station} - Copy',
        {station: stationName}
    );
    loading.value = false;
    error.value = null;
    cloneUrl.value = stationCloneUrl;

    $modal.value.show();
};

const clearContents = () => {
    resetForm();
    cloneUrl.value = null;
};

const close = () => {
    $modal.value.hide();
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doSubmit = () => {
    ifValid(() => {
        error.value = null;

        wrapWithLoading(
            axios({
                method: 'POST',
                url: cloneUrl.value,
                data: form.value
            })
        ).then(() => {
            notifySuccess();
            emit('relist');
            close();
        }).catch((error) => {
            error.value = error.response.data.message;
        });
    });
};

defineExpose({
    create
});
</script>
