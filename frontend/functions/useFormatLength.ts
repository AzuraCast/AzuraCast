import { useTranslate } from "~/vendor/gettext";
import { useLuxon } from "~/vendor/luxon";

/**
 * Returns a helper that formats a length in seconds as a human-readable
 * duration or a localized "None" for zero length.
 */
export function useFormatLength(): (length: number) => string {
    const { Duration } = useLuxon();
    const { $gettext } = useTranslate();

    return (length: number): string => {
        if (length === 0) {
            return $gettext("None");
        }

        return Duration.fromMillis(length * 1000)
            .rescale()
            .toHuman();
    };
}
