module.exports = {
    extends: [
        'eslint:recommended',
        'plugin:vue/vue3-recommended'
    ],
    rules: {
        "vue/no-v-html": "off",
        "vue/multi-word-component-names": "off",
        "no-unused-vars": ["error", {
            "varsIgnorePattern": "^_|props",
        }],
        "vue/html-indent": ["error", 4, {
            "attribute": 1,
            "baseIndent": 1,
            "closeBracket": 0,
            "alignAttributesVertically": true
        }]
    }
}
