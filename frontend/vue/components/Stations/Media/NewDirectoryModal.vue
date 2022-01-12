<template>
    <b-modal id="create_directory" centered ref="modal" :title="langNewDirectory">
        <b-form @submit.prevent="doMkdir">
            <b-wrapped-form-group id="new_directory_name" :field="$v.newDirectory" autofocus>
                <template #label="{lang}">
                    <translate :key="lang">Directory Name</translate>
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" @click="close" key="lang_btn_close" v-translate>
                Close
            </b-button>
            <b-button :variant="($v.$invalid) ? 'danger' : 'primary'" @click="doMkdir" key="lang_btn_create"
                      v-translate>
                Create Directory
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import {validationMixin} from 'vuelidate';
import {required} from 'vuelidate/dist/validators.min.js';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'NewDirectoryModal',
    components: {BWrappedFormGroup},
    mixins: [validationMixin],
    props: {
        currentDirectory: String,
        mkdirUrl: String
    },
    data() {
        return {
            newDirectory: null
        };
    },
    validations: {
        newDirectory: {
            required
        }
    },
    computed: {
        langNewDirectory () {
            return this.$gettext('New Directory');
        }
    },
    methods: {
        close () {
            this.newDirectory = null;
            this.$v.$reset();
            this.$refs.modal.hide();
        },
        doMkdir () {
            this.$v.$touch();
            if (this.$v.$anyError) {
                return;
            }

            this.$wrapWithLoading(
                this.axios.post(this.mkdirUrl, {
                    'currentDirectory': this.currentDirectory,
                    'name': this.newDirectory
                })
            ).then(() => {
                this.$notifySuccess(this.$gettext('New directory created.'));
            }).finally(() => {
                this.$emit('relist');
                this.close();
            });
        }
    }
};
</script>
