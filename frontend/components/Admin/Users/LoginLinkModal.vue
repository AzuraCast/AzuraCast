<template>
    <modal
        id="login_link_modal"
        ref="$modal"
        size="lg"
        :title="$gettext('New Login Link')"
        @hidden="clearContents"
    >
        <template #default>
            <div
                v-show="error != null"
                class="alert alert-danger"
            >
                {{ error }}
            </div>

            <form
                v-if="loginLink === null"
                class="form vue-form"
                @submit.prevent="doSubmit"
            >
                <div class="row">
                    <div class="col-md-6">
                        <form-group-multi-check
                            id="edit_form_type"
                            :field="r$.type"
                            :options="typeOptions"
                            stacked
                            radio
                            :label="$gettext('Link Type')"
                            class="mb-3"
                        />

                        <form-group-field
                            id="edit_form_expires_minutes"
                            :field="r$.expires_minutes"
                            :label="$gettext('Link Expiration (Minutes)')"
                        >
                            <template #default="{id, model}">
                                <radio-with-custom-number
                                    :id="id"
                                    v-model="model.$model"
                                    :options="expiresMinutesOptions"
                                />
                            </template>
                        </form-group-field>
                    </div>
                    <div class="col-md-6">
                        <form-group-field
                            id="form_comments"
                            ref="$field"
                            :field="r$.comment"
                            autofocus
                            :label="$gettext('Description/Comments')"
                        />
                    </div>
                </div>

                <invisible-submit-button/>
            </form>

            <div v-else>
                <login-link-new-link :login-link="loginLink"/>
            </div>
        </template>

        <template #modal-footer="slotProps">
            <slot
                name="modal-footer"
                v-bind="slotProps"
            >
                <button
                    type="button"
                    class="btn btn-secondary"
                    @click="hide"
                >
                    {{ $gettext('Close') }}
                </button>
                <button
                    v-if="loginLink === null"
                    type="submit"
                    class="btn"
                    :class="(r$.$invalid) ? 'btn-danger' : 'btn-primary'"
                    @click="doSubmit"
                >
                    {{ $gettext('Create New Login Link') }}
                </button>
            </slot>
        </template>
    </modal>
</template>

<script setup lang="ts">
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton.vue";
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed, ref, useTemplateRef} from "vue";
import {getErrorAsString, useAxios} from "~/vendor/axios";
import Modal from "~/components/Common/Modal.vue";
import {useHasModal} from "~/functions/useHasModal.ts";
import {ApiAdminNewLoginToken, ApiAdminNewLoginTokenResponse, LoginTokenTypes} from "~/entities/ApiInterfaces.ts";
import {useAppRegle} from "~/vendor/regle.ts";
import LoginLinkNewLink from "~/components/Admin/Users/LoginLinkNewLink.vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useTranslate} from "~/vendor/gettext.ts";
import RadioWithCustomNumber from "~/components/Common/RadioWithCustomNumber.vue";

const props = defineProps<{
    createUrl: string,
}>();

const error = ref<string | null>(null);
const loginLink = ref<string | null>(null);

type NewToken = Required<ApiAdminNewLoginToken>;

const form = ref<NewToken>({
    user: 0,
    type: LoginTokenTypes.ResetPassword,
    comment: null,
    expires_minutes: 30,
});

const {r$} = useAppRegle(
    form,
    {},
    {}
);

const clearContents = () => {
    r$.$reset({
        toOriginalState: true
    });

    error.value = null;
    loginLink.value = null;
};

const $modal = useTemplateRef('$modal');
const {show, hide} = useHasModal($modal);

const {axios} = useAxios();

const doSubmit = async () => {
    const {valid, data: postData} = await r$.$validate();
    if (!valid) {
        return;
    }

    error.value = null;

    try {
        const {data} = await axios.post<ApiAdminNewLoginTokenResponse>(
            props.createUrl,
            postData
        );

        loginLink.value = data.links.login;
    } catch (e) {
        error.value = getErrorAsString(e);
    }
};

const create = (userId: number) => {
    clearContents();

    form.value.user = userId;

    show();
};

const {$gettext} = useTranslate();

const typeOptions = computed(() => ([
    {
        value: LoginTokenTypes.ResetPassword,
        text: $gettext('Reset Password')
    },
    {
        value: LoginTokenTypes.Login,
        text: $gettext('Magic Login Link')
    }
]));

const expiresMinutesOptions = computed(() => [5, 15, 30, 60, 120].map(
    (row) => ({
        value: row,
        text: String(row)
    })
));

defineExpose({
    create
});
</script>
