<template>
    <b-modal id="rename_file" centered ref="modal" :title="langRenameFile">
        <b-form @submit.prevent="doRename">
            <b-wrapped-form-group id="new_directory_name" :field="$v.form.newPath" autofocus>
                <template #label="{lang}">
                    <translate :key="lang">New File Name</translate>
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" @click="close">
                <translate key="lang_btn_close">Close</translate>
            </b-button>
            <b-button :variant="($v.form.$invalid) ? 'danger' : 'primary'" @click="doRename">
                <translate key="lang_btn_rename">Rename</translate>
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'RenameModal',
    components: {BWrappedFormGroup},
    mixins: [validationMixin],
    props: {
        renameUrl: String
    },
    data() {
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

            this.$wrapWithLoading(
                this.axios.put(this.renameUrl, this.form)
            ).finally(() => {
                this.$refs.modal.hide();
                this.$emit('relist');
            });
        }
    }
};
</script>
