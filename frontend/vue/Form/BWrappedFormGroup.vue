<template>
    <b-form-group v-bind="$attrs" :label-class="labelClassWithRequired" :label-for="id" :state="fieldState">
        <template #default>
            <slot name="default" v-bind="{ id, field, state: fieldState }">
                <b-form-input type="text" :id="id" v-model="field.$model"
                              :state="fieldState"></b-form-input>
            </slot>

            <b-form-invalid-feedback :state="fieldState">
                <vuelidate-error :field="field"></vuelidate-error>
            </b-form-invalid-feedback>
        </template>

        <template #label="slotProps"><slot name="label" v-bind="slotProps"></slot></template>
        <template #description="slotProps"><slot name="description" v-bind="slotProps"></slot></template>

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
        labelClass: {
            type: String,
            default: ''
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
