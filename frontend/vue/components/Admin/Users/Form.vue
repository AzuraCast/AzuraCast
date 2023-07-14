<template>
    <div class="row g-3">
        <form-group-field
            id="edit_form_email"
            class="col-md-6"
            :field="v$.email"
            input-type="email"
            :label="$gettext('E-mail Address')"
        />

        <form-group-field
            id="edit_form_new_password"
            class="col-md-6"
            :field="v$.new_password"
            input-type="password"
            :label="$gettext('Password')"
        >
            <template
                v-if="isEditMode"
                #description
            >
                {{ $gettext('Leave blank to use the current password.') }}
            </template>
        </form-group-field>

        <form-group-field
            id="edit_form_name"
            class="col-md-12"
            :field="v$.name"
            :label="$gettext('Display Name')"
        />

        <form-group-multi-check
            id="edit_form_roles"
            class="col-md-12"
            :field="v$.roles"
            :options="roleOptions"
            :label="$gettext('Roles')"
        />
    </div>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed} from "vue";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {useVModel} from "@vueuse/core";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {email, required} from "@vuelidate/validators";
import validatePassword from "~/functions/validatePassword";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    roles: {
        type: Object,
        required: true
    },
    isEditMode: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['update:form']);
const form = useVModel(props, 'form', emit);

const {v$} = useVuelidateOnFormTab(
    computed(() => {
        return {
            email: {required, email},
            new_password: (props.isEditMode)
                ? {validatePassword}
                : {required, validatePassword},
            name: {},
            roles: {}
        }
    }),
    form,
    {
        email: '',
        new_password: '',
        name: '',
        roles: [],
    }
);

const roleOptions = computed(() => {
    return objectToFormOptions(props.roles);
});
</script>
