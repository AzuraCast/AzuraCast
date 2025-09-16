<template>
    <tab
        :label="$gettext('Basic Info')"
        :item-header-class="tabClass"
    >
        <div class="row g-3">
            <form-group-field
                id="edit_form_streamer_username"
                class="col-md-6"
                :field="r$.streamer_username"
                :label="$gettext('Streamer Username')"
                :description="$gettext('The streamer will use this username to connect to the radio server.')"
            />

            <form-group-field
                id="edit_form_streamer_password"
                class="col-md-6"
                :field="r$.streamer_password"
                input-type="password"
                :label="$gettext('Streamer password')"
                :description="$gettext('The streamer will use this password to connect to the radio server.')"
            />
        </div>
        <div class="row g-3">
            <form-group-field
                id="edit_form_display_name"
                class="col-md-6"
                :field="r$.display_name"
                :label="$gettext('Streamer Display Name')"
                :description="$gettext('This is the informal display name that will be shown in API responses if the streamer/DJ is live.')"
            />

            <form-group-field
                id="edit_form_comments"
                class="col-md-6"
                :field="r$.comments"
                input-type="textarea"
                :label="$gettext('Comments')"
                :description="$gettext('Internal notes or comments about the user, visible only on this control panel.')"
            />
        </div>
        <div class="row g-3 mt-3">
            <form-group-checkbox
                id="form_edit_is_active"
                class="col-md-6"
                :field="r$.is_active"
                :label="$gettext('Account is Active')"
                :description="$gettext('Enable to allow this account to log in and stream.')"
            />

            <form-group-checkbox
                id="form_edit_enforce_schedule"
                class="col-md-6"
                :field="r$.enforce_schedule"
                :label="$gettext('Enforce Schedule Times')"
                :description="$gettext('If enabled, this streamer will only be able to connect during their scheduled broadcast times.')"
            />
        </div>
    </tab>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed} from "vue";
import Tab from "~/components/Common/Tab.vue";
import {storeToRefs} from "pinia";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {useStationsStreamersForm} from "~/components/Stations/Streamers/Form/form.ts";

const {r$} = storeToRefs(useStationsStreamersForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.basicInfoTab));

</script>
