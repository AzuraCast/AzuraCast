<template>
    <form-fieldset>
        <div class="row g-3">
            <form-group-field
                id="form_name"
                class="col-md-6"
                :field="form.name"
            >
                <template #label>
                    {{ $gettext('Name') }}
                </template>
            </form-group-field>

            <form-group-field
                id="form_email"
                class="col-md-6"
                :field="form.email"
            >
                <template #label>
                    {{ $gettext('E-mail Address') }}
                </template>
            </form-group-field>
        </div>
    </form-fieldset>

    <form-fieldset>
        <template #label>
            {{ $gettext('Customization') }}
        </template>

        <div class="row g-3">
            <div class="col-md-6">
                <form-group-field
                    id="edit_form_locale"
                    :field="form.locale"
                >
                    <template #label>
                        {{ $gettext('Language') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :options="localeOptions"
                        />
                    </template>
                </form-group-field>
            </div>
            <div class="col-md-6">
                <form-group-field
                    id="edit_form_show_24_hour_time"
                    :field="form.show_24_hour_time"
                >
                    <template #label>
                        {{ $gettext('Time Display') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :options="show24hourOptions"
                        />
                    </template>
                </form-group-field>
            </div>
        </div>
    </form-fieldset>
</template>

<script setup>
import FormGroupField from "~/components/Form/FormGroupField";
import FormFieldset from "~/components/Form/FormFieldset";
import objectToFormOptions from "~/functions/objectToFormOptions";
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

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
    let localeOptions = objectToFormOptions(props.supportedLocales);
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
