import {DateTime, Settings} from 'luxon';
import {useAzuraCast} from "~/vendor/azuracast";

const {localeWithDashes, timeConfig} = useAzuraCast();

document.addEventListener('DOMContentLoaded', (): void => {
    Settings.defaultLocale = localeWithDashes;
});

export function useLuxon() {
    const timestampToRelative = (timestamp: number | null | undefined): string => {
        if (typeof timestamp !== 'number') {
            return '';
        }

        return DateTime.fromSeconds(timestamp).toRelative({
            ...timeConfig
        });
    }

    return {
        DateTime,
        timestampToRelative
    }
}
