import { StreamFormats } from "~/entities/ApiInterfaces";
import strtoupper from "~/functions/strtoupper";

export default function showFormatAndBitrate(
    format: string | StreamFormats | null,
    bitrate: number | null = null
): string {
    if (format === null) {
        return '';
    }

    if (format === 'flac' || bitrate === null) {
        return strtoupper(format);
    }

    return bitrate + 'kbps ' + strtoupper(format);
}
