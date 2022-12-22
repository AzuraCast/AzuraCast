<template>
    <modal-form ref="modal" :loading="loading" :title="$gettext('Clone Station')" :error="error"
                :disable-save-button="v$.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <admin-stations-clone-modal-form :form="v$"></admin-stations-clone-modal-form>

    </modal-form>
</template>

<script setup>
import useVuelidate from "@vuelidate/core";
import {required} from '@vuelidate/validators';
import ModalForm from "~/components/Common/ModalForm";
import AdminStationsCloneModalForm from "~/components/Admin/Stations/CloneModalForm";
import {ref} from "vue";
import gettext from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const emit = defineEmits(['relist']);

const loading = ref(true);
const cloneUrl = ref(null);
const error = ref(null);

const blankForm = {
    name: '',
    description: '',
    clone: [],
};

const form = ref({...blankForm});

const validations = {
    name: {required},
    description: {},
    clone: {}
};

const v$ = useVuelidate(validations, form);

const resetForm = () => {
    form.value = {...blankForm};
};

const modal = ref(); // BVModal
const {$gettext} = gettext;

const create = (stationName, stationCloneUrl) => {
    resetForm();

    form.value.name = $gettext(
        '%{station} - Copy',
        {station: stationName}
    );
    loading.value = false;
    error.value = null;
    cloneUrl.value = stationCloneUrl;

    modal.value.show();
};

const clearContents = () => {
    resetForm();
    cloneUrl.value = null;
};

const close = () => {
    modal.value.hide();
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doSubmit = () => {
    v$.value.$touch();
    if (v$.value.$errors.length > 0) {
        return;
    }

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
};

defineExpose({
    create
});
</script>
