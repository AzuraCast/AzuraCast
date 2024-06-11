export default function strtoupper(data: string | null): string {
    if (!data) {
        return '';
    }

    const upper: string[] = [];
    data.split(' ').forEach((word) => {
        upper.push(word.toUpperCase());
    });
    return upper.join(' ');
}
