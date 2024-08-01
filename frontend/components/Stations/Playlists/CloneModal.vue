<template>
    <modal-form
        id="clone_modal"
        ref="$modal"
        :title="$gettext('Duplicate Playlist')"
        :disable-save-button="v$.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <div class="row g-3">
            <form-group-field
                id="form_edit_name"
                class="col-md-12"
                :field="v$.name"
                :label="$gettext('New Playlist Name')"
            />

            <form-group-multi-check
                id="form_edit_clone"
                class="col-md-12"
                :field="v$.clone"
                :options="copyOptions"
                stacked
                :label="$gettext('Customize Copy')"
            />
        </div>
    </modal-form>
</template>

<script setup lang="ts">
import {required} from '@vuelidate/validators';
import FormGroupField from "~/components/Form/FormGroupField.vue";
import ModalForm from "~/components/Common/ModalForm.vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {ModalFormTemplateRef} from "~/functions/useBaseEditModal.ts";

const emit = defineEmits(['relist', 'needs-restart']);

const cloneUrl = ref(null);

const {form, v$, resetForm, ifValid} = useVuelidateOnForm(
    {
        'name': {required},
        'clone': {}
    },
    {
        'name': '',
        'clone': []
    }
);

const clearContents = () => {
    cloneUrl.value = null;
    resetForm();
};

const {$gettext} = useTranslate();

const copyOptions = [
    {
        value: 'media',
        text: $gettext('Copy associated media and folders.')
    },
    {
        value: 'schedule',
        text: $gettext('Copy scheduled playback times.')
    }
];

const $modal = ref<ModalFormTemplateRef>(null);

const open = (name, newCloneUrl) => {
    clearContents();

    cloneUrl.value = newCloneUrl;
    form.value.name = $gettext(
        '%{name} - Copy',
        {name: name}
    );

    $modal.value.show();
};

const {notifySuccess} = useNotify();
const {axios} = useAxios();

const doSubmit = () => {
    ifValid(() => {
        axios({
            method: 'POST',
            url: cloneUrl.value,
            data: form.value
        }).then(() => {
            notifySuccess();
            emit('needs-restart');
            emit('relist');
            $modal.value.hide();
        });
    });
};

defineExpose({
    open
});
</script>
