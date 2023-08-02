export default function (seconds) {
    seconds = parseInt(seconds);

    const d = Math.floor(seconds / 86400),
        h = Math.floor(seconds / 3600) % 24,
        m = Math.floor(seconds / 60) % 60,
        s = seconds % 60;

    return (d > 0 ? d + 'd ' : '')
        + (h > 0 ? ('0' + h).slice(-2) + ':' : '')
        + ('0' + m).slice(-2) + ':'
        + ('0' + s).slice(-2);
}
