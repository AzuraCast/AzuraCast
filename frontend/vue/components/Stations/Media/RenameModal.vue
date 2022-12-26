<template>
    <b-modal id="rename_file" centered ref="modal" :title="$gettext('Rename File/Directory')">
        <b-form @submit.prevent="doRename">
            <b-wrapped-form-group id="new_directory_name" :field="v$.form.newPath" autofocus>
                <template #label>
                    {{ $gettext('New File Name') }}
                </template>
            </b-wrapped-form-group>
        </b-form>
        <template #modal-footer>
            <b-button variant="default" @click="close">
                {{ $gettext('Close') }}
            </b-button>
            <b-button :variant="(v$.form.$invalid) ? 'danger' : 'primary'" @click="doRename">
                {{ $gettext('Rename') }}
            </b-button>
        </template>
    </b-modal>
</template>
<script>
import useVuelidate from "@vuelidate/core";
import {required} from '@vuelidate/validators';
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";

export default {
    name: 'RenameModal',
    components: {BWrappedFormGroup},
    setup() {
        return {v$: useVuelidate()}
    },
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
    methods: {
        open(filePath) {
            this.form.file = filePath;
            this.form.newPath = filePath;

            this.$refs.modal.show();
        },
        close() {
            this.v$.$reset();
            this.$refs.modal.hide();
        },
        doRename() {
            this.v$.$touch();
            if (this.v$.$errors.length > 0) {
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
