<template>
    <b-modal size="md centered" id="api_keys_modal" ref="modal" :title="langTitle" :busy="loading"
             @hidden="clearContents">
        <template #default="slotProps">
            <b-overlay variant="card" :show="loading">
                <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>

                <b-form v-if="newKey === null" class="form vue-form" @submit.prevent="doSubmit">
                    <b-form-fieldset>
                        <b-wrapped-form-group id="form_comments" :field="$v.form.comments">
                            <template #label="{lang}">
                                <translate :key="lang">API Key Description/Comments</translate>
                            </template>
                        </b-wrapped-form-group>
                    </b-form-fieldset>

                    <invisible-submit-button/>
                </b-form>

                <div v-else>
                    <account-api-key-new-key :key="newKey"></account-api-key-new-key>
                </div>
            </b-overlay>
        </template>

        <template #modal-footer="slotProps">
            <slot name="modal-footer" v-bind="slotProps">
                <b-button variant="default" type="button" @click="close">
                    <translate key="lang_btn_close">Close</translate>
                </b-button>
                <b-button v-if="newKey === null" variant="primary" type="submit" @click="doSubmit"
                          :disabled="$v.form.$invalid">
                    <translate v-if="isEditMode" key="lang_btn_save_changes">Save Changes</translate>
                    <translate v-else key="lang_btn_create_key">Create New Key</translate>
                </b-button>
            </slot>
        </template>
    </b-modal>
</template>

<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import BFormFieldset from "~/components/Form/BFormFieldset";
import InvisibleSubmitButton from "~/components/Common/InvisibleSubmitButton";
import AccountApiKeyNewKey from "./ApiKeyNewKey";
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'AccountApiKeyModal',
    components: {BWrappedFormGroup, AccountApiKeyNewKey, InvisibleSubmitButton, BFormFieldset},
    mixins: [validationMixin, BaseEditModal],
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit API Key')
                : this.$gettext('Add API Key');
        }
    },
    validations() {
        return {
            form: {
                comment: {required}
            }
        };
    },
    data() {
        return {
            newKey: null,
        }
    },
    methods: {
        resetForm() {
            this.newKey = null;
            this.form = {
                comment: ''
            };
        },
        onSubmitSuccess(response) {
            if (this.isEditMode) {
                this.$notifySuccess();
                this.close();
            } else {
                this.newKey = response.data.key;
            }

            this.$emit('relist');
        },
    }
};
</script>
