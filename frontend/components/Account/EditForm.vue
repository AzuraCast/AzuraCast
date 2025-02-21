<template>
    <div class="row g-3">
        <div class="col-md-6">
            <form-group-field
                id="form_name"
                class="mb-3"
                tabindex="1"
                :field="form.name"
                :label="$gettext('Name')"
            />

            <form-group-field
                id="form_email"
                class="mb-3"
                tabindex="2"
                :field="form.email"
                :label="$gettext('E-mail Address')"
            />

            <time-radios
                id="edit_form_show_24_hour_time"
                class="mb-3"
                tabindex="3"
                :field="form.show_24_hour_time"
            />
        </div>
        <div class="col-md-6">
            <form-group-multi-check
                id="edit_form_locale"
                tabindex="4"
                :field="form.locale"
                :options="localeOptions"
                stacked
                radio
                :label="$gettext('Language')"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import FormGroupField from "~/components/Form/FormGroupField.vue";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";
import {HasGenericFormProps} from "~/entities/Forms.ts";
import {objectToSimpleFormOptions} from "~/functions/objectToFormOptions.ts";
import TimeRadios from "~/components/Account/TimeRadios.vue";

interface AccountEditFormProps extends HasGenericFormProps {
    supportedLocales: Record<string, string>
}

const props = defineProps<AccountEditFormProps>();

const {$gettext} = useTranslate();

const localeOptions = computed(() => {
    const localeOptions = objectToSimpleFormOptions(props.supportedLocales).value;

    localeOptions.unshift({
        text: $gettext('Use Browser Default'),
        value: 'default'
    });

    return localeOptions;
});
</script>
