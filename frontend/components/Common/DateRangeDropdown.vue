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
import { RootProps, VueDatePicker } from "@vuepic/vue-datepicker";
import { isString } from "es-toolkit";
import { merge } from "es-toolkit/compat";
import type { DateTime as LuxonDateTime } from "luxon";
import { storeToRefs } from "pinia";
import { computed } from "vue";
import { getDateFnLocale } from "~/functions/getDateFnLocale.ts";
import { useTheme } from "~/functions/theme.ts";
import { useTranslate } from "~/vendor/gettext.ts";
import { useLuxon } from "~/vendor/luxon.ts";
import IconIcDateRange from "~icons/ic/baseline-date-range";

defineOptions({
    inheritAttrs: false,
});

export interface DateRange {
    startDate: Date;
    endDate: Date;
}

const props = defineProps<{
    options?: Partial<RootProps>;
    modelValue?: DateRange;
}>();

const emit =
    defineEmits<(e: "update:modelValue", modelValue: DateRange) => void>();

const { isDark } = storeToRefs(useTheme());

const { DateTime } = useLuxon();

type DateRangeTuple = Date[] | null;

type RangeFromNow = (nowTz: LuxonDateTime) => [LuxonDateTime, LuxonDateTime];

const dateRange = computed<DateRangeTuple>({
    get() {
        if (!props.modelValue) {
            return null;
        }

        return [props.modelValue.startDate, props.modelValue.endDate];
    },
    set(newValue) {
        if (newValue === null) {
            return;
        }

        const newRange = {
            startDate: newValue[0],
            endDate: newValue[1],
        };

        emit("update:modelValue", newRange);
    },
});

const { $gettext } = useTranslate();

const getTimezone = (options?: Partial<RootProps>): string => {
    if (options !== undefined && "timezone" in options && options.timezone) {
        if (isString(options.timezone)) {
            return options.timezone;
        }
    }

    return "UTC";
};

const ranges = computed(() => {
    const tz = getTimezone(props.options);

    const relativeRange = (getRange: RangeFromNow) => (): Date[] => {
        const nowTz = DateTime.now().setZone(tz);
        return getRange(nowTz).map((date) => date.toJSDate());
    };

    const lastDays = (days: number) =>
        relativeRange((nowTz) => [
            nowTz.minus({ days: days - 1 }).startOf("day"),
            nowTz.endOf("day"),
        ]);

    return [
        {
            label: $gettext("Last 24 Hours"),
            value: relativeRange((nowTz) => [nowTz.minus({ days: 1 }), nowTz]),
        },
        {
            label: $gettext("Today"),
            value: relativeRange((nowTz) => [
                nowTz.startOf("day"),
                nowTz.endOf("day"),
            ]),
        },
        {
            label: $gettext("Yesterday"),
            value: relativeRange((nowTz) => {
                const yesterday = nowTz.minus({ days: 1 });
                return [yesterday.startOf("day"), yesterday.endOf("day")];
            }),
        },
        {
            label: $gettext("Last 7 Days"),
            value: lastDays(7),
        },
        {
            label: $gettext("Last 14 Days"),
            value: lastDays(14),
        },
        {
            label: $gettext("Last 30 Days"),
            value: lastDays(30),
        },
        {
            label: $gettext("This Month"),
            value: relativeRange((nowTz) => [
                nowTz.startOf("month"),
                nowTz.endOf("month"),
            ]),
        },
        {
            label: $gettext("Last Month"),
            value: relativeRange((nowTz) => {
                const lastMonth = nowTz.minus({ months: 1 });
                return [lastMonth.startOf("month"), lastMonth.endOf("month")];
            }),
        },
    ];
});

const vueDatePickerOptions = computed<RootProps>(() => {
    return merge(
        {
            dark: isDark.value,
            range: {
                partialRange: false,
            },
            timeConfig: {
                enableTimePicker: false,
            },
            presetDates: ranges.value,
            locale: getDateFnLocale(),
            actionRow: {
                selectBtnLabel: $gettext("Select"),
                cancelBtnLabel: $gettext("Cancel"),
                nowBtnLabel: $gettext("Now"),
            },
            inputAttrs: {
                clearable: false,
            },
        },
        props.options,
    );
});
</script>
