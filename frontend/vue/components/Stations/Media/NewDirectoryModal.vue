<template>
    <modal
        id="create_directory"
        ref="$modal"
        centered
        :title="$gettext('New Directory')"
    >
        <form @submit.prevent="doMkdir">
            <form-group-field
                id="new_directory_name"
                :field="v$.newDirectory"
                autofocus
                :label="$gettext('Directory Name')"
            />
        </form>
        <template #modal-footer>
            <button
                type="button"
                class="btn btn-secondary"
                @click="close"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                type="button"
                class="btn"
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doMkdir"
            >
                {{ $gettext('Create Directory') }}
            </button>
        </template>
    </modal>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import FormGroupField from "~/components/Form/FormGroupField";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import Modal from "~/components/Common/Modal.vue";

const props = defineProps({
    currentDirectory: {
        type: String,
        required: true
    },
    mkdirUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const {form, v$, resetForm, ifValid} = useVuelidateOnForm(
    {
        newDirectory: {required}
    },
    {
        newDirectory: null
    }
);

const $modal = ref(); // Template Ref

const close = () => {
    resetForm();

    $modal.value.hide();
};

const open = () => {
    $modal.value.show();
};

const {wrapWithLoading, notifySuccess} = useNotify();
const {axios} = useAxios();
const {$gettext} = useTranslate();

const doMkdir = () => {
    ifValid(() => {
        wrapWithLoading(
            axios.post(props.mkdirUrl, {
                'currentDirectory': props.currentDirectory,
                'name': form.value.newDirectory
            })
        ).then(() => {
            notifySuccess($gettext('New directory created.'));
        }).finally(() => {
            emit('relist');
            close();
        });
    });
};

defineExpose({
    open
});
</script>
