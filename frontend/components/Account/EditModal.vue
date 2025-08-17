<template>
    <modal-form
        ref="$modal"
        :loading="loading"
        :title="$gettext('Edit Profile')"
        :error="error"
        :disable-save-button="r$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <div class="row g-3">
            <div class="col-md-6">
                <form-group-field
                    id="form_name"
                    class="mb-3"
                    tabindex="1"
                    :field="r$.name"
                    :label="$gettext('Name')"
                />

                <form-group-field
                    id="form_email"
                    class="mb-3"
                    tabindex="2"
                    :field="r$.email"
                    :label="$gettext('E-mail Address')"
                />

                <time-radios
                    id="edit_form_show_24_hour_time"
                    class="mb-3"
                    tabindex="3"
                    :field="r$.show_24_hour_time"
                />
            </div>
            <div class="col-md-6">
                <form-group-multi-check
                    id="edit_form_locale"
                    tabindex="4"
                    :field="r$.locale"
                    :options="localeOptions"
                    stacked
                    radio
                    :label="$gettext('Language')"
                />
            </div>
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import mergeExisting from "~/functions/mergeExisting";
import {email, required} from "@regle/rules";
import ModalForm from "~/components/Common/ModalForm.vue";
import {computed, ref, useTemplateRef} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {getApiUrl} from "~/router.ts";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useResettableRef} from "~/functions/useResettableRef.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import TimeRadios from "~/components/Account/TimeRadios.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import {objectToSimpleFormOptions} from "~/functions/objectToFormOptions.ts";

defineProps<{
    supportedLocales: Record<string, string>
}>();

const emit = defineEmits<{
    (e: 'reload'): void
}>();

const userUrl = getApiUrl('/frontend/account/me');

const loading = ref(true);
const error = ref(null);

const {record: form, reset: resetForm} = useResettableRef({
    name: '',
    email: '',
    locale: 'default',
    show_24_hour_time: false,
});

const {r$} = useAppRegle(
    form,
    {
        name: {},
        email: {required, email},
        locale: {required},
        show_24_hour_time: {}
    }
);

const clearContents = () => {
    resetForm();
    loading.value = false;
    error.value = null;
};

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const open = () => {
    clearContents();

    show();

    axios.get(userUrl.value).then((resp) => {
        form.value = mergeExisting(form.value, resp.data);
        loading.value = false;
    }).catch(() => {
        hide();
    });
};

const {$gettext} = useTranslate();

const localeOptions = computed(() => {
    const localeOptions = objectToSimpleFormOptions(props.supportedLocales).value;

    localeOptions.unshift({
        text: $gettext('Use Browser Default'),
        value: 'default'
    });

    return localeOptions;
});

const doSubmit = async () => {
    const {valid} = r$.$validate();
    if (!valid) {
        return;
    }

    error.value = null;

    try {
        await axios({
            method: 'PUT',
            url: userUrl.value,
            data: form.value
        });

        notifySuccess();
        emit('reload');
        hide();
    } catch (e) {
        error.value = e.response.data.message;
    }
};

defineExpose({
    open
});
</script>
