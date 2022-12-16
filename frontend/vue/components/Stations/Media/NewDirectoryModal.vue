<template>
    <b-modal id="create_directory" centered ref="modal" :title="langNewDirectory">
        <b-form @submit.prevent="doMkdir">
            <b-wrapped-form-group id="new_directory_name" :field="v$.newDirectory" autofocus>
                <template #label>
                    {{ $gettext('Directory Name') }}
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" @click="close">
                {{ $gettext('Close') }}
            </b-button>
            <b-button :variant="(v$.$invalid) ? 'danger' : 'primary'" @click="doMkdir">
                {{ $gettext('Create Directory') }}
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import useVuelidate from "@vuelidate/core";
import {required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'NewDirectoryModal',
    components: {BWrappedFormGroup},
    setup() {
        return {v$: useVuelidate()}
    },
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
        close() {
            this.newDirectory = null;
            this.v$.$reset();
            this.$refs.modal.hide();
        },
        doMkdir() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
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
