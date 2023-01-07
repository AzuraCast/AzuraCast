<template>
    <b-modal
        id="create_directory"
        ref="$modal"
        centered
        :title="$gettext('New Directory')"
    >
        <b-form @submit.prevent="doMkdir">
            <b-wrapped-form-group
                id="new_directory_name"
                :field="v$.newDirectory"
                autofocus
            >
                <template #label>
                    {{ $gettext('Directory Name') }}
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button
                variant="default"
                @click="close"
            >
                {{ $gettext('Close') }}
            </b-button>
            <b-button
                :variant="(v$.$invalid) ? 'danger' : 'primary'"
                @click="doMkdir"
            >
                {{ $gettext('Create Directory') }}
            </b-button>
        </template>
    </b-modal>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";

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
</script>
