module.exports = {
    input: {
        path: './vue',
        include: ["**/*.js", "**/*.ts", "**/*.vue"]
    },
    output: {
        path: '../translations',
        potPath: './frontend.pot',
        jsonPath: './translations.json',
        flat: false,
        linguas: false
    }
};
