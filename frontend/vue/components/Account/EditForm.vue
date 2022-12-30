<template>
    <b-form-fieldset>
        <div class="form-row">
            <b-wrapped-form-group
                id="form_name"
                class="col-md-6"
                :field="form.name"
            >
                <template #label>
                    {{ $gettext('Name') }}
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group
                id="form_email"
                class="col-md-6"
                :field="form.email"
            >
                <template #label>
                    {{ $gettext('E-mail Address') }}
                </template>
            </b-wrapped-form-group>
        </div>
    </b-form-fieldset>

    <b-form-fieldset>
        <template #label>
            {{ $gettext('Customization') }}
        </template>

        <div class="form-row">
            <b-col md="6">
                <b-wrapped-form-group
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
                </b-wrapped-form-group>
            </b-col>
            <b-col md="6">
                <b-wrapped-form-group
                    id="edit_form_theme"
                    :field="form.theme"
                >
                    <template #label>
                        {{ $gettext('Site Theme') }}
                    </template>
                    <template #default="slotProps">
                        <b-form-radio-group
                            :id="slotProps.id"
                            v-model="slotProps.field.$model"
                            stacked
                            :options="themeOptions"
                        />
                    </template>
                </b-wrapped-form-group>

                <b-wrapped-form-group
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
                </b-wrapped-form-group>
            </b-col>
        </div>
    </b-form-fieldset>
</template>

<script setup>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import BFormFieldset from "~/components/Form/BFormFieldset";
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

const themeOptions = computed(() => {
    return [
        {
            text: $gettext('Prefer System Default'),
            value: 'browser'
        },
        {
            text: $gettext('Light'),
            value: 'light'
        },
        {
            text: $gettext('Dark'),
            value: 'dark'
        }
    ];
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
