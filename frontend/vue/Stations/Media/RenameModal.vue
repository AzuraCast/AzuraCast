<template>
    <b-modal id="rename_file" centered ref="modal" :title="langRenameFile">
        <b-form @submit.prevent="doRename">
            <b-form-group label-for="new_directory_name">
                <template v-slot:label>
                    <translate key="lang_new_directory_name">New File Name</translate>
                </template>
                <b-input type="text" id="new_directory_name" v-model="$v.form.newPath.$model"
                         :state="$v.form.newPath.$dirty ? !$v.form.newPath.$error : null" autofocus></b-input>
                <b-form-invalid-feedback>
                    <translate key="lang_error_required">This field is required.</translate>
                </b-form-invalid-feedback>
            </b-form-group>
        </b-form>
        <template v-slot:modal-footer>
            <b-button variant="default" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button variant="primary" @click="doRename" :disabled="$v.form.$invalid">
                <translate key="lang_btn_rename">Rename</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import { validationMixin } from 'vuelidate';
import { required } from 'vuelidate/lib/validators';
import axios from 'axios';
import handleAxiosError from '../../Function/handleAxiosError';

export default {
    name: 'RenameModal',
    mixins: [validationMixin],
    props: {
        renameUrl: String
    },
    data () {
        return {
            form: {
                file: null,
                newPath: null
            }
        };
    },
    validations: {
        form: {
            newPath: {
                required
            }
        }
    },
    computed: {
        langRenameFile () {
            return this.$gettext('Rename File/Directory');
        }
    },
    methods: {
        open (filePath) {
            this.form.file = filePath;
            this.form.newPath = filePath;

            this.$refs.modal.show();
        },
        close () {
            this.$v.form.$reset();
            this.$refs.modal.hide();
        },
        doRename () {
            this.$v.form.$touch();
            if (this.$v.form.$anyError) {
                return;
            }

            axios.put(this.renameUrl, this.form).then((resp) => {
                this.$refs.modal.hide();
                this.$emit('relist');
            }).catch((err) => {
                handleAxiosError(err);

                this.$refs.modal.hide();
                this.$emit('relist');
            });
        }
    }
};
</script>
