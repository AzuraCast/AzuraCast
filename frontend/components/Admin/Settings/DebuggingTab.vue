<template>
    <tab
        :label="$gettext('Debugging')"
        :item-header-class="tabClass"
    >
        <form-fieldset>
            <div class="row g-3">
                <form-group-checkbox
                    id="edit_form_sync_disabled"
                    class="col-md-6"
                    :field="r$.sync_disabled"
                    :label="$gettext('Temporarily Disable Synchronization Tasks')"
                    :description="$gettext('Prevents background tasks from automatically running. Tasks are disabled automatically when restoring a backup to allow media to be re-uploaded.')"
                    advanced
                />
            </div>
        </form-fieldset>
    </tab>
</template>

<script setup lang="ts">
import FormFieldset from "~/components/Form/FormFieldset.vue";
import FormGroupCheckbox from "~/components/Form/FormGroupCheckbox.vue";
import {computed} from "vue";
import Tab from "~/components/Common/Tab.vue";
import {useAdminSettingsForm} from "~/components/Admin/Settings/form.ts";
import {useFormTabClass} from "~/functions/useFormTabClass.ts";
import {storeToRefs} from "pinia";

const {r$} = storeToRefs(useAdminSettingsForm());

const tabClass = useFormTabClass(computed(() => r$.value.$groups.debuggingTab));
</script>
