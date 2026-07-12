export default function (size: number): string {
    const i: number = Math.floor(Math.log(size) / Math.log(1024));

    const sizeNum = (size / 1024 ** i).toFixed(2);
    const sizeUnit = ["B", "kB", "MB", "GB", "TB"][i];
    return `${sizeNum} ${sizeUnit}`;
}
