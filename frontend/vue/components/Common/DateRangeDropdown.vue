<template>
    <date-range-picker
        v-bind="$props"
        ref="picker"
        v-model="dateRange"
        v-model:date-range="dateRange"
        control-container-class=""
        opens="left"
        show-dropdowns
        :time-picker-increment="1"
        :ranges="ranges"
        @select="onSelect"
    >
        <template #input="datePicker">
            <a
                id="reportrange"
                class="btn btn-dark dropdown-toggle"
                href="#"
                @click.prevent=""
            >
                <icon icon="date_range" />
                <span>
                    {{ datePicker.rangeText }}
                </span>
            </a>
        </template>

        <template
            v-for="(_, slot) of $slots"
            #[slot]="scope"
        >
            <slot
                :name="slot"
                v-bind="scope"
            />
        </template>
    </date-range-picker>
</template>

<script setup>
import DateRangePicker from 'vue3-daterange-picker';
import Icon from "./Icon";
import {DateTime} from 'luxon';
import {computed} from "vue";
import {useTranslate} from "~/vendor/gettext";

const props = defineProps({
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
    modelValue: {
        type: Object,
        required: true
    },
    customRanges: {
        type: [Object, Boolean],
        default: null,
    }
});

const emit = defineEmits(['update:modelValue', 'update']);

const dateRange = computed({
    get: () => {
        return props.modelValue;
    },
    set: () => {
        // Noop
    }
});

const {$gettext} = useTranslate();

const ranges = computed(() => {
    if (null !== props.customRanges) {
        return props.customRanges;
    }

    let nowTz = DateTime.now().setZone(props.tz);
    let nowAtMidnightDate = nowTz.endOf('day').toJSDate();

    let ranges = {};

    ranges[$gettext('Last 24 Hours')] = [
        nowTz.minus({days: 1}).toJSDate(),
        nowTz.toJSDate()
    ];
    ranges[$gettext('Today')] = [
        nowTz.minus({days: 1}).startOf('day').toJSDate(),
        nowAtMidnightDate
    ];
    ranges[$gettext('Yesterday')] = [
        nowTz.minus({days: 2}).startOf('day').toJSDate(),
        nowTz.minus({days: 1}).endOf('day').toJSDate()
    ];
    ranges[$gettext('Last 7 Days')] = [
        nowTz.minus({days: 7}).startOf('day').toJSDate(),
        nowAtMidnightDate
    ];
    ranges[$gettext('Last 14 Days')] = [
        nowTz.minus({days: 14}).startOf('day').toJSDate(),
        nowAtMidnightDate
    ];
    ranges[$gettext('Last 30 Days')] = [
        nowTz.minus({days: 30}).startOf('day').toJSDate(),
        nowAtMidnightDate
    ];
    ranges[$gettext('This Month')] = [
        nowTz.startOf('month').startOf('day').toJSDate(),
        nowTz.endOf('month').endOf('day').toJSDate()
    ];
    ranges[$gettext('Last Month')] = [
        nowTz.minus({months: 1}).startOf('month').startOf('day').toJSDate(),
        nowTz.minus({months: 1}).endOf('month').endOf('day').toJSDate()
    ];

    return ranges;
});

const onSelect = (range) => {
    emit('update:modelValue', range);
    emit('update', range);
};
</script>

<script>
export default {
    inheritAttrs: false,
    model: {
        prop: 'modelValue',
        event: 'update:modelValue'
    },
}
</script>

<style lang="scss">
@import 'vue3-daterange-picker/src/assets/daterangepicker';
</style>
