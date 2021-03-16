<template>
    <b-tab :title="langTitle">
        <b-form-group>
            <b-row>
                <b-form-group v-for="field in customFields" :key="field.short_name" class="col-md-6" :label-for="'edit_form_custom_'+field.short_name"
                              :label="field.name">
                    <b-form-input type="text" :id="'edit_form_custom_'+field.short_name"
                                  v-model="form.custom_fields[field.short_name].$model"></b-form-input>
                </b-form-group>
            </b-row>
        </b-form-group>
    </b-tab>
</template>

<script>
export default {
    name: 'MediaFormCustomFields',
    props: {
        form: Object,
        customFields: Array
    },
    computed: {
        langTitle () {
            return this.$gettext('Custom Fields');
        }
    }
};
</script>
