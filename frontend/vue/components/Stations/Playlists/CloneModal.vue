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

<script setup>
import {required} from '@vuelidate/validators';
import FormGroupField from "~/components/Form/FormGroupField";
import ModalForm from "~/components/Common/ModalForm";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

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

const $modal = ref(); // Template Ref

const open = (name, newCloneUrl) => {
    clearContents();

    cloneUrl.value = newCloneUrl;
    form.value.name = $gettext(
        '%{name} - Copy',
        {name: name}
    );

    $modal.value.show();
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();

const doSubmit = () => {
    ifValid(() => {
        wrapWithLoading(
            axios({
                method: 'POST',
                url: cloneUrl.value,
                data: form.value
            })
        ).then(() => {
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
