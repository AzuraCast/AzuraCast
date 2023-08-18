export default function (url: string | null): string | null {
    if (url === null) {
        return null;
    }

    return url.split(/[?#]/)[0];
}
