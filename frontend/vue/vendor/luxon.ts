import {DateTime, Duration, Settings} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";

const {localeWithDashes, timeConfig} = useAzuraCast();

document.addEventListener('DOMContentLoaded', (): void => {
    Settings.defaultLocale = localeWithDashes;
});

interface TimestampToRelative {
    (timestamp: number | null | undefined): string;
}

interface UseLuxon {
    DateTime: DateTime,
    Duration: Duration,
    timestampToRelative: TimestampToRelative
}

export function useLuxon(): UseLuxon {
    const timestampToRelative: TimestampToRelative = (timestamp: number | null | undefined): string => {
        if (typeof timestamp !== 'number') {
            return '';
        }

        return DateTime.fromSeconds(timestamp).toRelative({
            ...timeConfig
        });
    }

    return {
        DateTime,
        Duration,
        timestampToRelative
    }
}
