<template>
    <b-form-group>
        <b-row>
            <b-wrapped-form-group class="col-md-6" id="edit_form_name" :field="form.name">
                <template #label>
                    <translate key="lang_form_name">Field Name</translate>
                </template>
                <template #description>
                    <translate key="lang_form_name_desc">This will be used as the label when editing individual songs, and will show in API results.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_short_name" :field="form.short_name">
                <template #label>
                    <translate key="lang_form_short_name">Programmatic Name</translate>
                </template>
                <template #description>
                    <translate key="lang_form_short_name_desc">Optionally specify an API-friendly name, such as "field_name". Leave this field blank to automatically create one based on the name.</translate>
                </template>
            </b-wrapped-form-group>

            <b-wrapped-form-group class="col-md-6" id="edit_form_auto_assign" :field="form.auto_assign">
                <template #label>
                    <translate key="lang_form_auto_assign">Automatically Set from ID3v2 Value</translate>
                </template>
                <template #description>
                    <translate key="lang_form_auto_assign_desc">Optionally select an ID3v2 metadata field that, if present, will be used to set this field's value.</translate>
                </template>
                <template #default="props">
                    <b-form-select :id="props.id" v-model="props.field.$model"
                                   :options="autoAssignOptions"></b-form-select>
                </template>
            </b-wrapped-form-group>
        </b-row>
    </b-form-group>
</template>

<script>
import BWrappedFormGroup from "~/components/Form/BWrappedFormGroup";
import _ from 'lodash';

export default {
    name: 'AdminCustomFieldsForm',
    components: {BWrappedFormGroup},
    props: {
        form: Object,
        autoAssignTypes: Object
    },
    computed: {
        autoAssignOptions() {
            let autoAssignOptions = [
                {
                    text: this.$gettext('Disable'),
                    value: '',
                }
            ];

            _.forEach(this.autoAssignTypes, (typeName, typeKey) => {
                autoAssignOptions.push({
                    text: typeName,
                    value: typeKey
                });
            });

            return autoAssignOptions;
        },
    }
};
</script>
