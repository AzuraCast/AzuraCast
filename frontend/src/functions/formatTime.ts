export default function (seconds: string | number) {
    seconds = Math.floor(Number(seconds));

    const d: number = Math.floor(seconds / 86400),
        h: number = Math.floor(seconds / 3600) % 24,
        m: number = Math.floor(seconds / 60) % 60,
        s: number = seconds % 60;

    return (d > 0 ? d + 'd ' : '')
        + (h > 0 ? ('0' + h).slice(-2) + ':' : '')
        + ('0' + m).slice(-2) + ':'
        + ('0' + s).slice(-2);
}
