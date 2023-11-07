module.exports = {
    input: {
        path: './src',
        include: ["**/*.js", "**/*.ts", "**/*.vue"]
    },
    output: {
        path: '../translations',
        potPath: './frontend.pot',
        jsonPath: './translations.json',
        locales: [],
        flat: false,
        linguas: false
    }
};
