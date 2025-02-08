import strtoupper from "~/functions/strtoupper";

export default function showFormatAndBitrate(format: string, bitrate: number): string {
    if (format === 'flac') {
        return strtoupper(format);
    }

    return bitrate + 'kbps ' + strtoupper(format);
}
