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
import {DateTime} from 'luxon';

export default {
    name: 'DateRangeDropdown',
    components: {DateRangePicker, Icon},
    emits: ['update', 'input'],
    model: {
        prop: 'dateRange',
        event: 'update',
    },
    props: {
        tz: {
            type: String,
            default: 'system'
        },
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
                let nowTz = DateTime.now().setZone(this.tz);
                let nowTzDate = nowTz.toJSDate();

                let ranges = {};
                ranges[this.$gettext('Today')] = [
                    nowTzDate,
                    nowTzDate
                ];
                ranges[this.$gettext('Yesterday')] = [
                    nowTz.minus({days: 1}).toJSDate(),
                    nowTz.minus({days: 1}).toJSDate()
                ];
                ranges[this.$gettext('Last 7 Days')] = [
                    nowTz.minus({days: 6}).toJSDate(),
                    nowTzDate
                ];
                ranges[this.$gettext('Last 14 Days')] = [
                    nowTz.minus({days: 13}).toJSDate(),
                    nowTzDate
                ];
                ranges[this.$gettext('Last 30 Days')] = [
                    nowTz.minus({days: 29}).toJSDate(),
                    nowTzDate
                ];
                ranges[this.$gettext('This Month')] = [
                    nowTz.startOf('month').toJSDate(),
                    nowTz.endOf('month').toJSDate()
                ];
                ranges[this.$gettext('Last Month')] = [
                    nowTz.minus({months: 1}).startOf('month').toJSDate(),
                    nowTz.minus({months: 1}).endOf('month').toJSDate()
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
