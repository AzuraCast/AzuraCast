<template>
    <b-modal size="lg" id="edit_modal" ref="modal" :title="langTitle" :busy="loading">
        <b-overlay variant="card" :show="loading">
            <b-alert variant="danger" :show="error != null">{{ error }}</b-alert>
            <b-form class="form" @submit.prevent="doSubmit">
                <b-tabs content-class="mt-3">
                    <remote-form-basic-info :form="$v.form"></remote-form-basic-info>
                    <remote-form-auto-dj :form="$v.form"></remote-form-auto-dj>
                </b-tabs>

                <invisible-submit-button/>
            </b-form>
        </b-overlay>
        <template #modal-footer>
            <b-button variant="default" type="button" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" type="submit" @click="doSubmit" :disabled="$v.form.$invalid">
                <translate key="lang_btn_save">Save Changes</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import InvisibleSubmitButton from '../../Common/InvisibleSubmitButton';
import BaseEditModal from '../../Common/BaseEditModal';
import RemoteFormBasicInfo from "./Form/BasicInfo";
import RemoteFormAutoDj from "./Form/AutoDj";
import {REMOTE_ICECAST} from "../../Entity/RadioAdapters";

export default {
    name: 'RemoteEditModal',
    mixins: [BaseEditModal],
    components: {
        RemoteFormAutoDj,
        RemoteFormBasicInfo,
        InvisibleSubmitButton
    },
    props: {
        enableAdvancedFeatures: Boolean
    },
    validations() {
        return {
            form: {
                display_name: {},
                is_visible_on_public_pages: {},
                type: {required},
                enable_autodj: {},
                autodj_format: {},
                autodj_bitrate: {},
                custom_listen_url: {},
                url: {required},
                mount: {},
                admin_password: {},
                source_port: {},
                source_mount: {},
                source_username: {},
                source_password: {},
                is_public: {},
            }
        };
    },
    computed: {
        langTitle() {
            return this.isEditMode
                ? this.$gettext('Edit Remote Relay')
                : this.$gettext('Add Remote Relay');
        }
    },
    methods: {
        resetForm() {
            this.form = {
                display_name: null,
                is_visible_on_public_pages: true,
                type: REMOTE_ICECAST,
                enable_autodj: false,
                autodj_format: null,
                autodj_bitrate: null,
                custom_listen_url: null,
                url: null,
                mount: null,
                admin_password: null,
                source_port: null,
                source_mount: null,
                source_username: null,
                source_password: null,
                is_public: false
            };
        }
    }
};
</script>
