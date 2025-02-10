import {useLuxon} from "~/vendor/luxon.ts";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast.ts";
import {DateTimeMaybeValid} from "luxon";

export default function useStationDateTimeFormatter(
    timezone?: string
) {
    const {DateTime} = useLuxon();
    const {timeConfig} = useAzuraCast();

    if (!timezone) {
        const station = useAzuraCastStation();
        if (station) {
            timezone = station.timezone;
        } else {
            throw new Error("Cannot get timezone!");
        }
    }

    const now = (): DateTimeMaybeValid =>
        DateTime.local({zone: timezone});

    const timestampToDateTime = (value: number): DateTimeMaybeValid =>
        DateTime.fromSeconds(value, {zone: timezone});

    const isoToDateTime = (value: string): DateTimeMaybeValid =>
        DateTime.fromISO(value, {zone: timezone});

    const formatDateTime = (
        value: DateTimeMaybeValid,
        format: Intl.DateTimeFormatOptions
    ) => value.toLocaleString(
        {...format, ...timeConfig}
    );

    const formatDateTimeAsDateTime = (
        value: DateTimeMaybeValid,
        format: Intl.DateTimeFormatOptions | null = null
    ) => formatDateTime(value, format ?? DateTime.DATETIME_MED);

    const formatDateTimeAsTime = (
        value: DateTimeMaybeValid,
        format: Intl.DateTimeFormatOptions | null = null
    ) => formatDateTime(value, format ?? DateTime.TIME_WITH_SECONDS);

    const formatDateTimeAsRelative = (
        value: DateTimeMaybeValid
    ) => value.toRelative();

    const formatTimestampAsDateTime = (
        value: number | null,
        format: Intl.DateTimeFormatOptions | null = null
    ) =>
        (value)
            ? formatDateTimeAsDateTime(timestampToDateTime(value), format)
            : ''

    const formatTimestampAsTime = (
        value: number | null,
        format: Intl.DateTimeFormatOptions | null = null
    ) =>
        (value)
            ? formatDateTimeAsTime(timestampToDateTime(value), format)
            : ''

    const formatTimestampAsRelative = (value: number | null) =>
        (value)
            ? formatDateTimeAsRelative(timestampToDateTime(value))
            : '';

    const formatIsoAsDateTime = (
        value: any,
        format: Intl.DateTimeFormatOptions | null = null
    ) => (value)
        ? formatDateTimeAsDateTime(isoToDateTime(value), format)
        : '';

    const formatIsoAsTime = (
        value: any,
        format: Intl.DateTimeFormatOptions | null = null
    ) => (value)
        ? formatDateTimeAsTime(isoToDateTime(value), format)
        : '';

    const formatIsoAsRelative = (value) =>
        (value)
            ? formatDateTimeAsRelative(isoToDateTime(value))
            : '';

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
        formatIsoAsRelative
    };
}
