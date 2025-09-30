<template>
    <vue-date-picker
        v-bind="vueDatePickerOptions"
        v-model="dateRange"
    >
        <template #dp-input="{ value }">
            <button
                type="button"
                class="btn dropdown-toggle"
                v-bind="$attrs"
            >
                <icon-ic-date-range/>

                <span>
                    {{ value }}
                </span>
            </button>
        </template>
    </vue-date-picker>
</template>

<script setup lang="ts">
import VueDatePicker, {VueDatePickerProps} from "@vuepic/vue-datepicker";
import {useTheme} from "~/functions/theme.ts";
import {useTranslate} from "~/vendor/gettext.ts";
import {computed} from "vue";
import {useAzuraCast} from "~/vendor/azuracast.ts";
import {useLuxon} from "~/vendor/luxon.ts";
import {storeToRefs} from "pinia";
import {isString} from "es-toolkit";
import IconIcDateRange from "~icons/ic/baseline-date-range";

defineOptions({
    inheritAttrs: false
});

export interface DateRange {
    startDate: Date,
    endDate: Date
}

const props = defineProps<{
    options?: Partial<VueDatePickerProps>,
    modelValue?: DateRange
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', modelValue: DateRange): void
}>();

const {isDark} = storeToRefs(useTheme());

const {localeWithDashes} = useAzuraCast();
const {DateTime} = useLuxon();

type DateRangeTuple = Date[] | null;

const dateRange = computed<DateRangeTuple>({
    get() {
        if (!props.modelValue) {
            return null;
        }

        return [
            props.modelValue.startDate,
            props.modelValue.endDate,
        ]
    },
    set(newValue) {
        if (newValue === null) {
            return;
        }

        const newRange = {
            startDate: newValue[0],
            endDate: newValue[1]
        };

        emit('update:modelValue', newRange);
    }
});

const {$gettext} = useTranslate();

const getTimezone = (options?: Partial<VueDatePickerProps>): string => {
    if (options !== undefined && 'timezone' in options && options.timezone) {
        if (isString(options.timezone)) {
            return options.timezone;
        }
        if ('timezone' in options.timezone && isString(options.timezone.timezone)) {
            return options.timezone.timezone;
        }
    }

    return 'UTC';
}

const ranges = computed(() => {
    const tz = getTimezone(props.options);

    const nowTz = DateTime.now().setZone(tz);
    const nowAtMidnightDate = nowTz.endOf('day').toJSDate();

    return [
        {
            label: $gettext('Last 24 Hours'),
            value: [
                nowTz.minus({days: 1}).toJSDate(),
                nowTz.toJSDate()
            ]
        },
        {
            label: $gettext('Today'),
            value: [
                nowTz.minus({days: 1}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ]
        },
        {
            label: $gettext('Yesterday'),
            value: [
                nowTz.minus({days: 2}).startOf('day').toJSDate(),
                nowTz.minus({days: 1}).endOf('day').toJSDate()
            ]
        },
        {
            label: $gettext('Last 7 Days'),
            value: [
                nowTz.minus({days: 7}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ]
        },
        {
            label: $gettext('Last 14 Days'),
            value: [
                nowTz.minus({days: 14}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ]
        },
        {
            label: $gettext('Last 30 Days'),
            value: [
                nowTz.minus({days: 30}).startOf('day').toJSDate(),
                nowAtMidnightDate
            ]
        },
        {
            label: $gettext('This Month'),
            value: [
                nowTz.startOf('month').startOf('day').toJSDate(),
                nowTz.endOf('month').endOf('day').toJSDate()
            ]
        },
        {
            label: $gettext('Last Month'),
            value: [
                nowTz.minus({months: 1}).startOf('month').startOf('day').toJSDate(),
                nowTz.minus({months: 1}).endOf('month').endOf('day').toJSDate()
            ]
        }
    ];
});

const vueDatePickerOptions = computed<VueDatePickerProps>(() => {
    return {
        dark: isDark.value,
        range: {
            partialRange: false
        },
        enableTimePicker: false,
        presetDates: ranges.value,
        locale: localeWithDashes,
        selectText: $gettext('Select'),
        cancelText: $gettext('Cancel'),
        nowButtonLabel: $gettext('Now'),
        clearable: false,
        ...props.options,
    }
});
</script>
