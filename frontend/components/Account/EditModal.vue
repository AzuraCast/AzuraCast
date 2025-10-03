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
import {useNotify} from "~/components/Common/Toasts/useNotify.ts";
import {getErrorAsString, useAxios} from "~/vendor/axios";
import {useHasModal} from "~/functions/useHasModal.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import TimeRadios from "~/components/Account/TimeRadios.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import {objectToSimpleFormOptions} from "~/functions/objectToFormOptions.ts";
import {useApiRouter} from "~/functions/useApiRouter.ts";

const props = defineProps<{
    supportedLocales: Record<string, string>
}>();

const emit = defineEmits<{
    (e: 'reload'): void
}>();

const {getApiUrl} = useApiRouter();
const userUrl = getApiUrl('/frontend/account/me');

const loading = ref(true);
const error = ref<string | null>(null);

type AccountRow = {
    name: string | null,
    email: string | null,
    locale: string | null,
    show_24_hour_time: boolean | null
}

const form = ref<AccountRow>({
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
    },
    {}
);

const clearContents = () => {
    r$.$reset({
        toOriginalState: true
    });
    
    loading.value = false;
    error.value = null;
};

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doOpen = async () => {
    clearContents();

    show();

    try {
        const {data} = await axios.get(userUrl.value);
        r$.$reset({
            toState: mergeExisting(r$.$value, data)
        });

        loading.value = false;
    } catch {
        hide();
    }
}

const open = () => {
    void doOpen();
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
    const {valid, data: postData} = await r$.$validate();
    if (!valid) {
        return;
    }

    error.value = null;

    try {
        await axios({
            method: 'PUT',
            url: userUrl.value,
            data: postData
        });

        notifySuccess();
        emit('reload');
        hide();
    } catch (e) {
        error.value = getErrorAsString(e);
    }
};

defineExpose({
    open
});
</script>
