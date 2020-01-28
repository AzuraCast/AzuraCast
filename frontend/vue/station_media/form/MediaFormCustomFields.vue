<template>
    <b-tab :title="langTitle" v-if="customFields.length > 0">
        <b-form-group>
            <b-row>
                <template v-for="(field, _) in customFields">
                    <b-form-group :key="field.key" class="col-md-6" :label-for="'edit_form_custom_'+field.key"
                                  :label="field.label">
                        <b-form-input type="text" :id="'edit_form_custom_'+field.key"
                                      v-model="form.custom_fields[field.key]"></b-form-input>
                    </b-form-group>
                </template>
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