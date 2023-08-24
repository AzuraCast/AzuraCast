module.exports = {
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended',
        "@vue/typescript/recommended",
    ],
    "parser": "vue-eslint-parser",
    "parserOptions": {
        "parser": "@typescript-eslint/parser",
    },
    plugins: ['@typescript-eslint'],
    rules: {
        "@typescript-eslint/no-unused-vars": ["error", {
            "varsIgnorePattern": "^_|props",
        }],
        "vue/multi-word-component-names": "off",
        "vue/html-indent": ["error", 4, {
            "attribute": 1,
            "baseIndent": 1,
            "closeBracket": 0,
            "alignAttributesVertically": true
        }],
        "vue/no-v-html": "off",
        "vue/no-mutating-props": "off",
        'vue/no-multiple-template-root': "off",
        'vue/no-setup-props-destructure': "off"
    }
}
