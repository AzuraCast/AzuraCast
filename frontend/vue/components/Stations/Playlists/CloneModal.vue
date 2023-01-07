<template>
    <modal-form
        id="clone_modal"
        ref="$modal"
        :title="$gettext('Duplicate Playlist')"
        :disable-save-button="v$.form.$invalid"
        @submit="doSubmit"
        @hidden="clearContents"
    >
        <div class="form-row">
            <b-wrapped-form-group
                id="form_edit_name"
                class="col-md-12"
                :field="v$.form.name"
            >
                <template #label>
                    {{ $gettext('New Playlist Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_edit_clone"
                class="col-md-12"
                :field="v$.form.clone"
            >
                <template #label>
                    {{ $gettext('Customize Copy') }}
                </template>
                <template #default="slotProps">
                    <b-form-checkbox-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        stacked
                    >
                        <b-form-checkbox value="media">
                            {{ $gettext('Copy associated media and folders.') }}
                        </b-form-checkbox>
                        <b-form-checkbox value="schedule">
                            {{ $gettext('Copy scheduled playback times.') }}
                        </b-form-checkbox>
                    </b-form-checkbox-group>
                </template>
            </b-wrapped-form-group>
        </div>
    </modal-form>
</template>

<script setup>
import {required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import ModalForm from "~/components/Common/ModalForm";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {ref} from "vue";
import {useTranslate} from "~/vendor/gettext";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

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
