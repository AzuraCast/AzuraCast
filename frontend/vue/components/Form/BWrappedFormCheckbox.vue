<template>
    <b-form-group v-bind="$attrs" :label-for="id" :state="fieldState">
        <template #default>
            <slot name="default" v-bind="{ id, field, state: fieldState }">
                <b-form-checkbox v-bind="inputAttrs" v-model="field.$model" :id="id" :name="name">
                    <slot name="label" :lang="'lang_'+id">

                    </slot>
                    <span v-if="isRequired" class="text-danger">
                        <span aria-hidden="true">*</span>
                        <span class="sr-only">Required</span>
                    </span>
                    <span v-if="advanced" class="badge small badge-primary">
                        {{ $gettext('Advanced') }}
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

        <template v-for="(_, slot) of filteredScopedSlots" v-slot:[slot]="scope">
            <slot :name="slot" v-bind="scope"></slot>
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
            return _.filter(this.$slots, (slot, name) => {
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
