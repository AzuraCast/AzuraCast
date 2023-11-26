<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Edit Profile')"
        :error="error"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <account-edit-form
            :form="v$"
            :supported-locales="supportedLocales"
        />
    </modal-form>
</template>

<script setup lang="ts">
import mergeExisting from "~/functions/mergeExisting";
import {email, required} from '@vuelidate/validators';
import AccountEditForm from "./EditForm.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import {ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {ModalFormTemplateRef} from "~/functions/useBaseEditModal.ts";
import {getApiUrl} from "~/router.ts";

const props = defineProps({
    supportedLocales: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['reload']);

const userUrl = getApiUrl('/frontend/account/me');

const loading = ref(true);
const error = ref(null);

const {form, resetForm, v$, ifValid} = useVuelidateOnForm(
    {
        name: {},
        email: {required, email},
        locale: {required},
        show_24_hour_time: {}
    },
    {
        name: '',
        email: '',
        locale: 'default',
        show_24_hour_time: null,
    }
);

const clearContents = () => {
    resetForm();
    loading.value = false;
    error.value = null;
};

const $modal = ref<ModalFormTemplateRef>(null);

const close = () => {
    $modal.value?.hide();
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const open = () => {
    clearContents();

    $modal.value?.show();

    axios.get(userUrl.value).then((resp) => {
        form.value = mergeExisting(form.value, resp.data);
        loading.value = false;
    }).catch(() => {
        close();
    });
};

const doSubmit = () => {
    ifValid(() => {
        error.value = null;

        axios({
            method: 'PUT',
            url: userUrl.value,
            data: form.value
        }).then(() => {
            notifySuccess();
            emit('reload');
            close();
        }).catch((error) => {
            error.value = error.response.data.message;
        });
    });
};

defineExpose({
    open
});
</script>
