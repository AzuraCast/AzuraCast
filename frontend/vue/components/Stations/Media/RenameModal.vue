<template>
    <b-modal
        id="rename_file"
        ref="$modal"
        centered
        :title="$gettext('Rename File/Directory')"
    >
        <b-form @submit.prevent="doRename">
            <b-wrapped-form-group
                id="new_directory_name"
                :field="v$.newPath"
                autofocus
            >
                <template #label>
                    {{ $gettext('New File Name') }}
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <button
                class="btn btn-secondary"
                @click="close"
            >
                {{ $gettext('Close') }}
            </button>
            <button
                class="btn"
                :class="(v$.$invalid) ? 'btn-danger' : 'btn-primary'"
                @click="doRename"
            >
                {{ $gettext('Rename') }}
            </button>
        </template>
    </b-modal>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useNotify} from "~/functions/useNotify";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    renameUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const file = ref(null);

const {form, v$, resetForm, ifValid} = useVuelidateOnForm(
    {
        newPath: {required}
    },
    {
        newPath: null
    }
);

const $modal = ref(); // Template Ref

const open = (filePath) => {
    file.value = filePath;
    form.value.newPath = filePath;

    $modal.value.show();
};

const close = () => {
    resetForm();
    file.value = null;

    $modal.value.hide();
};

const {wrapWithLoading} = useNotify();
const {axios} = useAxios();

const doRename = () => {
    ifValid(() => {
        wrapWithLoading(
            axios.put(props.renameUrl, {
                file: file.value,
                ...form.value
            })
        ).finally(() => {
            $modal.value.hide();
            emit('relist');
        });
    });
};

defineExpose({
    open
});
</script>
