export default function storageAvailable(type: string): boolean {
    try {
        const storage = window[type],
            x: string = '__storage_test__';
        storage.setItem(x, x);
        storage.removeItem(x);
        return true;
    } catch (e) {
        return false;
    }
}
