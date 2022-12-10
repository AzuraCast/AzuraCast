<template>
    <date-range-picker
        v-bind="$props" ref="picker" controlContainerClass="" opens="left" show-dropdowns
        :time-picker-increment="1" :ranges="ranges" @update="onUpdate">
        <template #input="datePicker">
            <a class="btn btn-bg dropdown-toggle" id="reportrange" href="#" @click.prevent="">
                <icon icon="date_range"></icon>
                {{ datePicker.rangeText }}
            </a>
        </template>

        <template v-for="(_, slot) of $slots" v-slot:[slot]="scope">
            <slot :name="slot" v-bind="scope"></slot>
        </template>
    </date-range-picker>
</template>

<style lang="scss">
@import '../../../node_modules/vue3-daterange-picker/src/assets/daterangepicker';
</style>

<script>
import DateRangePicker from 'vue3-daterange-picker';
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
        customRanges: {
            type: [Object, Boolean],
            default: null,
        },
    },
    computed: {

        ranges() {
            let ranges = {};

            if (null !== this.customRanges) {
                return this.customRanges;
            }

            let nowTz = DateTime.now().setZone(this.tz);
            let nowAtMidnightDate = nowTz.endOf('day').toJSDate();

            ranges[this.$gettext('Last 24 Hours')] = [
                nowTz.minus({days: 1}).toJSDate(),
                nowTz.toJSDate()
            ];
            ranges[this.$gettext('Today')] = [
                nowTz.minus({days: 1}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ];
            ranges[this.$gettext('Yesterday')] = [
                nowTz.minus({days: 2}).startOf('day').toJSDate(),
                nowTz.minus({days: 1}).endOf('day').toJSDate()
            ];
            ranges[this.$gettext('Last 7 Days')] = [
                nowTz.minus({days: 7}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ];
            ranges[this.$gettext('Last 14 Days')] = [
                nowTz.minus({days: 14}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ];
            ranges[this.$gettext('Last 30 Days')] = [
                nowTz.minus({days: 30}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ];
            ranges[this.$gettext('This Month')] = [
                nowTz.startOf('month').startOf('day').toJSDate(),
                nowTz.endOf('month').endOf('day').toJSDate()
            ];
            ranges[this.$gettext('Last Month')] = [
                nowTz.minus({months: 1}).startOf('month').startOf('day').toJSDate(),
                nowTz.minus({months: 1}).endOf('month').endOf('day').toJSDate()
            ];

            return ranges;
        }
    },
    methods: {
        onUpdate(newValue) {
            this.$emit('update', newValue);
        }
    }
}
</script>
