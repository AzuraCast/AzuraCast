<template>
    <div class="row g-3">
        <form-group-field
            id="edit_form_username"
            class="col-md-6"
            :field="v$.username"
        >
            <template #label>
                {{ $gettext('Username') }}
            </template>
        </form-group-field>

        <form-group-field
            id="edit_form_password"
            class="col-md-6"
            :field="v$.password"
            input-type="password"
        >
            <template
                v-if="isEditMode"
                #label
            >
                {{ $gettext('New Password') }}
            </template>
            <template
                v-else
                #label
            >
                {{ $gettext('Password') }}
            </template>

            <template
                v-if="isEditMode"
                #description
            >
                {{ $gettext('Leave blank to use the current password.') }}
            </template>
        </form-group-field>

        <form-group-field
            id="edit_form_publicKeys"
            class="col-md-12"
            :field="v$.publicKeys"
            input-type="textarea"
        >
            <template #label>
                {{ $gettext('SSH Public Keys') }}
            </template>
            <template #description>
                {{
                    $gettext('Optionally supply SSH public keys this user can use to connect instead of a password. Enter one key per line.')
                }}
            </template>
        </form-group-field>
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {FormTabEmits, FormTabProps, useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {computed} from "vue";
import {required} from "@vuelidate/validators";

interface SftpUsersFormProps extends FormTabProps {
    isEditMode: boolean
}

const props = defineProps<SftpUsersFormProps>();
const emit = defineEmits<FormTabEmits>();

const {v$} = useVuelidateOnFormTab(
    props,
    emit,
    computed(() => {
        return {
            username: {required},
            password: props.isEditMode ? {} : {required},
            publicKeys: {}
        }
    }),
    {
        username: '',
        password: null,
        publicKeys: null
    }
);
</script>
