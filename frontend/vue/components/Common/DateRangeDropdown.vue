<template>
    <date-range-picker
        ref="picker" controlContainerClass="" opens="left" show-dropdowns
        v-bind="$props" @update="onUpdate">
        <template #input="datePicker">
            <a class="btn btn-bg dropdown-toggle" id="reportrange" href="#" @click.prevent="">
                <icon icon="date_range"></icon>
                {{ datePicker.rangeText }}
            </a>
        </template>
        <slot v-for="(_, name) in $slots" :name="name" :slot="name"/>
        <template v-for="(_, name) in $scopedSlots" :slot="name" slot-scope="slotData">
            <slot :name="name" v-bind="slotData"/>
        </template>
    </date-range-picker>
</template>

<style lang="css">
@import '../../../node_modules/vue2-daterange-picker/dist/vue2-daterange-picker.css';
</style>

<script>
import DateRangePicker from 'vue2-daterange-picker';
import Icon from "./Icon";

export default {
    name: 'DateRangeDropdown',
    components: {DateRangePicker, Icon},
    emits: ['update', 'input'],
    model: {
        prop: 'dateRange',
        event: 'update',
    },
    props: {
        minDate: {
            type: [String, Date],
            default() {
                return null
            }
        },
        maxDate: {
            type: [String, Date],
            default() {
                return null
            }
        },
        timePicker: {
            type: Boolean,
            default: false,
        },
        dateRange: { // for v-model
            type: [Object],
            default: null,
            required: true
        },
        ranges: {
            type: [Object, Boolean],
            default() {
                let ranges = {};
                ranges[this.$gettext('Today')] = [
                    moment().toDate(),
                    moment().toDate()
                ];
                ranges[this.$gettext('Yesterday')] = [
                    moment().subtract(1, 'days').toDate(),
                    moment().subtract(1, 'days').toDate()
                ];
                ranges[this.$gettext('Last 7 Days')] = [
                    moment().subtract(6, 'days').toDate(),
                    moment().toDate()
                ];
                ranges[this.$gettext('Last 14 Days')] = [
                    moment().subtract(13, 'days').toDate(),
                    moment().toDate()
                ];
                ranges[this.$gettext('Last 30 Days')] = [
                    moment().subtract(29, 'days').toDate(),
                    moment().toDate()
                ];
                ranges[this.$gettext('This Month')] = [
                    moment().startOf('month').toDate(),
                    moment().endOf('month').toDate()
                ];
                ranges[this.$gettext('Last Month')] = [
                    moment().subtract(1, 'month').startOf('month').toDate(),
                    moment().subtract(1, 'month').endOf('month').toDate()
                ];

                return ranges;
            }
        },
    },
    methods: {
        onUpdate(newValue) {
            this.$emit('update', newValue);
        }
    }


}
</script>
