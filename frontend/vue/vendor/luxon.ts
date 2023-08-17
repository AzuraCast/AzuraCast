import {DateTime, Duration, Settings} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";

interface TimestampToRelative {
    (timestamp: number | null | undefined): string;
}

interface UseLuxon {
    DateTime: typeof DateTime,
    Duration: typeof Duration,
    timestampToRelative: TimestampToRelative
}

export function useLuxon(): UseLuxon {
    const {localeWithDashes, timeConfig} = useAzuraCast();
    Settings.defaultLocale = localeWithDashes;

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
