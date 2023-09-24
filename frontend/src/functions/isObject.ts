export default function isObject(value) {
    return typeof value === "object"
        && (Object(value) === value)
        && !Array.isArray(value);
}
