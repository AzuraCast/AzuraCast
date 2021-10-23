<template>
    <b-form-group v-bind="$attrs" :label-class="labelClassWithRequired" :label-for="id" :state="fieldState">
        <template #default>
            <slot name="default" v-bind="{ id, field, state: fieldState }">
                <b-form-textarea v-if="inputType === 'textarea'" :id="id" v-model="field.$model"
                                 v-bind="inputAttrs" :state="fieldState"></b-form-textarea>
                <b-form-input v-else :type="inputType" :id="id" v-model="field.$model"
                              v-bind="inputAttrs" :state="fieldState"></b-form-input>
            </slot>

            <b-form-invalid-feedback :state="fieldState">
                <vuelidate-error :field="field"></vuelidate-error>
            </b-form-invalid-feedback>
        </template>

        <template #label="slotProps">
            <slot name="label" v-bind="slotProps"></slot>
            <span v-if="advanced" class="badge small badge-primary">
                <translate key="badge_advanced">Advanced</translate>
            </span>
        </template>
        <template #description="slotProps">
            <slot name="description" v-bind="slotProps"></slot>
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
    name: 'BWrappedFormGroup',
    components: {VuelidateError},
    props: {
        id: {
            type: String,
            required: true
        },
        field: {
            type: Object,
            required: true
        },
        inputType: {
            type: String,
            default: 'text'
        },
        inputAttrs: {
            type: Object,
            default() {
                return {};
            }
        },
        labelClass: {
            type: String,
            default: ''
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
                    'default', 'label', 'description'
                ], name);
            });
        },
        fieldState() {
            return this.field.$dirty ? !this.field.$error : null;
        },
        labelClassWithRequired () {
            let labelClass = this.labelClass;
            if (this.isRequired) {
                labelClass += ' required';
            }
            return labelClass;
        },
        isRequired() {
            return _.has(this.field, 'required');
        }
    }
}
</script>
