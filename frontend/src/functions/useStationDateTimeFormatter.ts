import {useLuxon} from "~/vendor/luxon.ts";
import {useAzuraCast, useAzuraCastStation} from "~/vendor/azuracast.ts";
import {DateTimeMaybeValid} from "luxon";

export default function useStationDateTimeFormatter() {
    const {DateTime, Duration} = useLuxon();
    const {timeConfig} = useAzuraCast();
    const {timezone} = useAzuraCastStation();

    const timestampToDateTime = (value): DateTimeMaybeValid =>
        DateTime.fromSeconds(value).setZone(timezone);

    const now = (): DateTimeMaybeValid =>
        DateTime.now().setZone(timezone);

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

    const formatNowAsDateTime = (
        format: Intl.DateTimeFormatOptions | null = null
    ) => formatDateTimeAsDateTime(now(), format);

    const formatNowAsTime = (
        format: Intl.DateTimeFormatOptions | null = null
    ) => formatDateTimeAsTime(now(), format);

    return {
        DateTime,
        Duration,
        timestampToDateTime,
        now,
        formatDateTime,
        formatDateTimeAsDateTime,
        formatDateTimeAsTime,
        formatDateTimeAsRelative,
        formatTimestampAsDateTime,
        formatTimestampAsTime,
        formatTimestampAsRelative,
        formatNowAsDateTime,
        formatNowAsTime
    };
}
