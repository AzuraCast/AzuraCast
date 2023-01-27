<template>
    <b-modal
        id="api_keys_modal"
        ref="$modal"
        size="md"
        centered
        :title="$gettext('Add API Key')"
        no-enforce-focus
        @hidden="clearContents"
    >
        <template #default>
            <b-alert
                variant="danger"
                :show="error != null"
            >
                {{ error }}
            </b-alert>

            <b-form
                v-if="newKey === null"
                class="form vue-form"
                @submit.prevent="doSubmit"
            >
                <b-form-fieldset>
                    <b-wrapped-form-group
                        id="form_comments"
                        :field="v$.comment"
                        autofocus
                    >
                        <template #label>
                            {{ $gettext('API Key Description/Comments') }}
                        </template>
                    </b-wrapped-form-group>
                </b-form-fieldset>

                <invisible-submit-button />
            </b-form>

            <div v-else>
                <account-api-key-new-key :new-key="newKey" />
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <b-button
                    variant="default"
                    type="button"
                    @click="close"
                >
                    {{ $gettext('Close') }}
                </b-button>
                <b-button
                    v-if="newKey === null"
                    :variant="(v$.$invalid) ? 'danger' : 'primary'"
                    type="submit"
                    @click="doSubmit"
                >
                    {{ $gettext('Create New Key') }}
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script setup>
import BFormFieldset from "~/components/Form/BFormFieldset";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";
import AccountApiKeyNewKey from "./ApiKeyNewKey";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import {required} from '@vuelidate/validators';
import {ref} from "vue";
import {useVuelidateOnForm} from "~/functions/useVuelidateOnForm";
import {useNotify} from "~/vendor/bootstrapVue";
import {useAxios} from "~/vendor/axios";

const props = defineProps({
    createUrl: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['relist']);

const error = ref(null);
const newKey = ref(null);

const {form, resetForm, v$, validate} = useVuelidateOnForm(
    {
        comment: {required}
    },
    {
        comment: ''
    }
);

const clearContents = () => {
    resetForm();
    error.value = null;
    newKey.value = null;
};

const $modal = ref(); // BModal

const create = () => {
    clearContents();

    $modal.value?.show();
};

const {wrapWithLoading} = useNotify();
const {axios} = useAxios();

const doSubmit = async () => {
    const isValid = await validate();
    if (!isValid) {
        return;
    }

    error.value = null;

    wrapWithLoading(
        axios({
            method: 'POST',
            url: props.createUrl,
            data: form.value
        })
    ).then((resp) => {
        newKey.value = resp.data.key;
        emit('relist');
    }).catch((error) => {
        error.value = error.response.data.message;
    });
};

const close = () => {
    $modal.value?.hide();
};

defineExpose({
    create
});
</script>
