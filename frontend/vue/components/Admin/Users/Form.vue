<template>
    <b-form-group>
        <div class="row g-3">
            <b-wrapped-form-group
                id="edit_form_email"
                class="col-md-6"
                :field="form.email"
                input-type="email"
            >
                <template #label>
                    {{ $gettext('E-mail Address') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_new_password"
                class="col-md-6"
                :field="form.new_password"
                input-type="password"
            >
                <template #label>
                    {{ $gettext('Password') }}
                </template>
                <template
                    v-if="isEditMode"
                    #description
                >
                    {{ $gettext('Leave blank to use the current password.') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_name"
                class="col-md-12"
                :field="form.name"
            >
                <template #label>
                    {{ $gettext('Display Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="edit_form_roles"
                class="col-md-12"
                :field="form.roles"
            >
                <template #label>
                    {{ $gettext('Roles') }}
                </template>
                <template #default="slotProps">
                    <b-form-checkbox-group
                        :id="slotProps.id"
                        v-model="slotProps.field.$model"
                        :options="roleOptions"
                    />
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-group>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup.vue";
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed} from "vue";

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

const roleOptions = computed(() => {
    return objectToFormOptions(props.roles);
});
</script>
