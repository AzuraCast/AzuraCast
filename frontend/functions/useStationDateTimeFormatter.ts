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
        value: any,
        format: Intl.DateTimeFormatOptions | null = null
    ) =>
        (value)
            ? formatDateTimeAsDateTime(timestampToDateTime(value), format)
            : ''

    const formatTimestampAsTime = (
        value: any,
        format: Intl.DateTimeFormatOptions | null = null
    ) =>
        (value)
            ? formatDateTimeAsTime(timestampToDateTime(value), format)
            : ''

    const formatTimestampAsRelative = (value) =>
        (value)
            ? formatDateTimeAsRelative(timestampToDateTime(value))
            : '';

    return {
        now,
        timestampToDateTime,
        formatDateTime,
        formatDateTimeAsDateTime,
        formatDateTimeAsTime,
        formatDateTimeAsRelative,
        formatTimestampAsDateTime,
        formatTimestampAsTime,
        formatTimestampAsRelative
    };
}
