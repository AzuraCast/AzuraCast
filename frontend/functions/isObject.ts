export default function isObject(value: any): boolean {
    return typeof value === "object"
        && (Object(value) === value)
        && !Array.isArray(value);
}
