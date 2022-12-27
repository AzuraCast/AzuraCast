export default function strtoupper(data) {
    if (!data) {
        return '';
    }

    let upper = [];
    data.split(' ').forEach((word) => {
        upper.push(word.toUpperCase());
    });
    return upper.join(' ');
};
