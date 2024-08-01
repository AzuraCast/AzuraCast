<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="edit_form_streamer_username"
                class="col-md-6"
                :field="v$.streamer_username"
                :label="$gettext('Streamer Username')"
                :description="$gettext('The streamer will use this username to connect to the radio server.')"
            />

            <form-group-field
                id="edit_form_streamer_password"
                class="col-md-6"
                :field="v$.streamer_password"
                input-type="password"
                :label="$gettext('Streamer password')"
                :description="$gettext('The streamer will use this password to connect to the radio server.')"
            />
        </div>
        <div class="row g-3">
            <form-group-field
                id="edit_form_display_name"
                class="col-md-6"
                :field="v$.display_name"
                :label="$gettext('Streamer Display Name')"
                :description="$gettext('This is the informal display name that will be shown in API responses if the streamer/DJ is live.')"
            />

            <form-group-field
                id="edit_form_comments"
                class="col-md-6"
                :field="v$.comments"
                input-type="textarea"
                :label="$gettext('Comments')"
                :description="$gettext('Internal notes or comments about the user, visible only on this control panel.')"
            />
        </div>
        <div class="row g-3 mt-3">
            <form-group-checkbox
                id="form_edit_is_active"
                class="col-md-6"
                :field="v$.is_active"
                :label="$gettext('Account is Active')"
                :description="$gettext('Enable to allow this account to log in and stream.')"
            />

            <form-group-checkbox
                id="form_edit_enforce_schedule"
                class="col-md-6"
                :field="v$.enforce_schedule"
                :label="$gettext('Enforce Schedule Times')"
                :description="$gettext('If enabled, this streamer will only be able to connect during their scheduled broadcast times.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {useVuelidateOnFormTab} from "~/functions/useVuelidateOnFormTab";
import {required} from "@vuelidate/validators";
import {computed} from "vue";
import {useVModel} from "@vueuse/core";
import Tab from "~/components/Common/Tab.vue";

const props = defineProps({
    form: {
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

const {v$, tabClass} = useVuelidateOnFormTab(
    computed(() => {
        return {
            streamer_username: {required},
            streamer_password: (props.isEditMode) ? {} : {required},
            display_name: {},
            comments: {},
            is_active: {},
            enforce_schedule: {}
        };
    }),
    form,
    {
        streamer_username: null,
        streamer_password: null,
        display_name: null,
        comments: null,
        is_active: true,
        enforce_schedule: false,
    }
);
</script>
