import { toRefs } from "@vueuse/core";
import { DateTime } from "luxon";
import { MaybeRefOrGetter, toValue } from "vue";
import { useStationData } from "~/functions/useStationQuery.ts";
import { useAzuraCast } from "~/vendor/azuracast.ts";
import { useLuxon } from "~/vendor/luxon.ts";

export default function useStationDateTimeFormatter(
    timezone?: MaybeRefOrGetter<string>,
) {
    const { DateTime } = useLuxon();
    const { timeConfig } = useAzuraCast();

    if (!timezone) {
        const stationData = useStationData();
        const { timezone: tz } = toRefs(stationData);
        timezone = tz;
    }

    const now = (): DateTime => DateTime.local({ zone: toValue(timezone) });

    const timestampToDateTime = (value: number): DateTime =>
        DateTime.fromSeconds(value, { zone: toValue(timezone) });

    const isoToDateTime = (value: string): DateTime =>
        DateTime.fromISO(value, { zone: toValue(timezone) });

    const formatDateTime = (
        value: DateTime,
        format: Intl.DateTimeFormatOptions,
    ) => value.toLocaleString({ ...format, ...timeConfig });

    const formatDateTimeAsDateTime = (
        value: DateTime,
        format: Intl.DateTimeFormatOptions | null = null,
    ) => formatDateTime(value, format ?? DateTime.DATETIME_MED);

    const formatDateTimeAsTime = (
        value: DateTime,
        format: Intl.DateTimeFormatOptions | null = null,
    ) => formatDateTime(value, format ?? DateTime.TIME_WITH_SECONDS);

    const formatDateTimeAsRelative = (value: DateTime) => value.toRelative();

    const formatTimestampAsDateTime = (
        value: number | null,
        format: Intl.DateTimeFormatOptions | null = null,
    ) =>
        value
            ? formatDateTimeAsDateTime(timestampToDateTime(value), format)
            : "";

    const formatTimestampAsTime = (
        value: number | null,
        format: Intl.DateTimeFormatOptions | null = null,
    ) =>
        value ? formatDateTimeAsTime(timestampToDateTime(value), format) : "";

    const formatTimestampAsRelative = (value: number | null) =>
        value ? formatDateTimeAsRelative(timestampToDateTime(value)) : "";

    const formatIsoAsDateTime = (
        value: any,
        format: Intl.DateTimeFormatOptions | null = null,
    ) => (value ? formatDateTimeAsDateTime(isoToDateTime(value), format) : "");

    const formatIsoAsTime = (
        value: any,
        format: Intl.DateTimeFormatOptions | null = null,
    ) => (value ? formatDateTimeAsTime(isoToDateTime(value), format) : "");

    const formatIsoAsRelative = (value: any) =>
        value ? formatDateTimeAsRelative(isoToDateTime(value)) : "";

    return {
        now,
        timestampToDateTime,
        isoToDateTime,
        formatDateTime,
        formatDateTimeAsDateTime,
        formatDateTimeAsTime,
        formatDateTimeAsRelative,
        formatTimestampAsDateTime,
        formatTimestampAsTime,
        formatTimestampAsRelative,
        formatIsoAsDateTime,
        formatIsoAsTime,
        formatIsoAsRelative,
    };
}
