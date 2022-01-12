<template>
    <b-form-group v-bind="$attrs" :label-for="id" :state="fieldState">
        <template #default>
            <slot name="default" v-bind="{ id, field, state: fieldState }">
                <b-form-checkbox :id="id" :name="name" v-model="field.$model" v-bind="inputAttrs">
                    <slot name="label" :lang="'lang_'+id">

                    </slot>
                    <span v-if="isRequired" class="text-danger">
                        <span aria-hidden="true">*</span>
                        <span class="sr-only">Required</span>
                    </span>
                    <span v-if="advanced" class="badge small badge-primary">
                        <translate key="badge_advanced">Advanced</translate>
                    </span>
                </b-form-checkbox>

                <b-form-invalid-feedback :state="fieldState">
                    <vuelidate-error :field="field"></vuelidate-error>
                </b-form-invalid-feedback>
            </slot>
        </template>

        <template #description="slotProps">
            <slot name="description" v-bind="slotProps" :lang="'lang_'+id+'_desc'"></slot>
        </template>

        <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
        <template v-for="(_, name) in filteredScopedSlots" :slot="name" slot-scope="slotData">
            <slot :name="name" v-bind="slotData"/>
        </template>
    </b-form-group>
</template>

<script>
import _ from "lodash";
import VuelidateError from "./VuelidateError";

export default {
    name: 'BWrappedFormCheckbox',
    components: {VuelidateError},
    props: {
        id: {
            type: String,
            required: true
        },
        name: {
            type: String,
        },
        field: {
            type: Object,
            required: true
        },
        inputAttrs: {
            type: Object,
            default() {
                return {};
            }
        },
        advanced: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        filteredScopedSlots() {
            return _.filter(this.$scopedSlots, (slot, name) => {
                return !_.includes([
                    'default', 'description'
                ], name);
            });
        },
        fieldState() {
            return this.field.$dirty ? !this.field.$error : null;
        },
        isRequired() {
            return _.has(this.field, 'required');
        }
    }
}
</script>
