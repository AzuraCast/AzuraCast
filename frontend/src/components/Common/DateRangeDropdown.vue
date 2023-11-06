<template>
    <vue-date-picker
        v-model="dateRange"
        :dark="isDark"
        range
        :partial-range="false"
        :preset-dates="ranges"
        :min-date="minDate"
        :max-date="maxDate"
        :locale="localeWithDashes"
        :select-text="$gettext('Select')"
        :cancel-text="$gettext('Cancel')"
        :now-button-label="$gettext('Now')"
        :timezone="tz"
        :clearable="false"
    >
        <template #dp-input="{ value }">
            <button
                type="button"
                class="btn btn-dark dropdown-toggle"
            >
                <icon :icon="IconDateRange" />
                <span>
                    {{ value }}
                </span>
            </button>
        </template>
    </vue-date-picker>
</template>

<script setup lang="ts">
import VueDatePicker from '@vuepic/vue-datepicker';
import Icon from "./Icon.vue";
import useTheme from "~/functions/theme";
import {useTranslate} from "~/vendor/gettext";
import {computed} from "vue";
import {useAzuraCast} from "~/vendor/azuracast";
import {useLuxon} from "~/vendor/luxon";
import {IconDateRange} from "~/components/Common/icons";

defineOptions({
    inheritAttrs: false
});

const props = defineProps({
    tz: {
        type: String,
        default: null
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
    }
});

const emit = defineEmits(['update:modelValue']);

const {isDark} = useTheme();

const {localeWithDashes} = useAzuraCast();
const {DateTime} = useLuxon();

const dateRange = computed({
    get() {
        return [
            props.modelValue?.startDate ?? null,
            props.modelValue?.endDate ?? null,
        ]
    },
    set(newValue) {
        const newRange = {
            startDate: newValue[0],
            endDate: newValue[1]
        };

        emit('update:modelValue', newRange);
    }
});

const {$gettext} = useTranslate();

const ranges = computed(() => {
    const nowTz = DateTime.now().setZone(props.tz);
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
</script>
