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

            <form-group-multi-check
                id="edit_form_show_24_hour_time"
                class="mb-3"
                tabindex="3"
                :field="form.show_24_hour_time"
                :options="show24hourOptions"
                stacked
                radio
                :label="$gettext('Time Display')"
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
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";
import FormGroupMultiCheck from "~/components/Form/FormGroupMultiCheck.vue";

const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    supportedLocales: {
        type: Object,
        required: true
    }
});

const {$gettext} = useTranslate();

const localeOptions = computed(() => {
    const localeOptions = objectToFormOptions(props.supportedLocales);
    localeOptions.unshift({
        text: $gettext('Use Browser Default'),
        value: 'default'
    });
    return localeOptions;
});

const show24hourOptions = computed(() => {
    return [
        {
            text: $gettext('Prefer System Default'),
            value: null
        },
        {
            text: $gettext('12 Hour'),
            value: false
        },
        {
            text: $gettext('24 Hour'),
            value: true
        }
    ];
});
</script>
