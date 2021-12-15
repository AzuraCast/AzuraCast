<template>
    <modal-form ref="modal" :loading="loading" :title="langTitle" :error="error" :disable-save-button="$v.form.$invalid"
                @submit="doSubmit" @hidden="clearContents">

        <b-tabs content-class="mt-3">
            <remote-form-basic-info :form="$v.form"></remote-form-basic-info>

            <remote-form-auto-dj :form="$v.form"></remote-form-auto-dj>
        </b-tabs>

    </modal-form>
</template>
<script>
import {required} from 'vuelidate/dist/validators.min.js';
import BaseEditModal from '~/components/Common/BaseEditModal';
import RemoteFormBasicInfo from "./Form/BasicInfo";
import RemoteFormAutoDj from "./Form/AutoDj";
import {REMOTE_ICECAST} from "~/components/Entity/RadioAdapters";

export default {
    name: 'RemoteEditModal',
    emits: ['needs-restart'],
    mixins: [BaseEditModal],
    components: {
        RemoteFormAutoDj,
        RemoteFormBasicInfo
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
        },
        onSubmitSuccess(response) {
            this.$notifySuccess();

            this.$emit('needs-restart');
            this.$emit('relist');

            this.close();
        },
    }
};
</script>
