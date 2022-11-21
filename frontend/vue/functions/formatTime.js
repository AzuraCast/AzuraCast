export default function (seconds) {
    let d = Math.floor(seconds / 86400),
        h = Math.floor(seconds / 3600) % 24,
        m = Math.floor(seconds / 60) % 60,
        s = seconds % 60;

    return (d > 0 ? d + 'd ' : '')
        + (h > 0 ? ('0' + h).slice(-2) + ':' : '')
        + (m > 0 ? ('0' + m).slice(-2) + ':' : '')
        + (seconds > 60 ? ('0' + s).slice(-2) : s);
}
