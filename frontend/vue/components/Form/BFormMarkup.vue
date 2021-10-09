<template>
    <b-form-group v-bind="$attrs" :label-for="id">
        <template #default="slotProps">
            <div :id="id">
                <slot name="default" v-bind="slotProps"></slot>
            </div>
        </template>

        <template #label="slotProps">
            <slot name="label" v-bind="slotProps"></slot>
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

export default {
    name: 'BFormMarkup',
    props: {
        id: {
            type: String,
            required: true
        },
    },
    computed: {
        filteredScopedSlots() {
            return _.filter(this.$scopedSlots, (slot, name) => {
                return !_.includes([
                    'default', 'label', 'description'
                ], name);
            });
        },
        labelClassWithRequired() {
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
